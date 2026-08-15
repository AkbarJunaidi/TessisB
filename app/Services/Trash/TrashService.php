<?php

namespace App\Services\Trash;

use App\Models\File;
use App\Models\Folder;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class TrashService
{
    /**
     * Katalog model yang aman & relevan untuk fitur Trash.
     *
     * Model lain yang juga memakai SoftDeletes (User, Comment, ProjectNote,
     * SuratJalan) sengaja TIDAK dimasukkan:
     * - User        : menghapus/memulihkan akun login berdampak ke autentikasi
     *                 & kepemilikan banyak data lain, di luar scope Trash data.
     * - Comment     : anak dari Task, tidak punya identitas "nama data" mandiri.
     * - ProjectNote : anak dari Project, idem seperti Comment.
     * - SuratJalan  : restore/permanent delete bisa mengubah state stok
     *                 Inventory (qty dipakai/dikembalikan) di luar scope ini.
     */
    private const TYPES = [
        'project' => [
            'model'       => Project::class,
            'table'       => 'projects',
            'name_column' => 'name',
            'label'       => 'Project',
        ],
        'task' => [
            'model'       => Task::class,
            'table'       => 'tasks',
            'name_column' => 'title',
            'label'       => 'Task',
        ],
        'inventory' => [
            'model'       => Inventory::class,
            'table'       => 'inventories',
            'name_column' => 'name',
            'label'       => 'Inventory',
        ],
        'folder' => [
            'model'       => Folder::class,
            'table'       => 'folders',
            'name_column' => 'name',
            'label'       => 'Folder',
        ],
        'file' => [
            'model'       => File::class,
            'table'       => 'files',
            'name_column' => 'file_name',
            'label'       => 'File',
        ],
    ];

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Daftar tipe data untuk dropdown filter "Tipe Data" (value => label).
     */
    public function getTypeOptions(): array
    {
        $options = [];

        foreach (self::TYPES as $key => $config) {
            $options[$key] = $config['label'];
        }

        return $options;
    }

    /**
     * Daftar user untuk dropdown filter "Dihapus Oleh".
     */
    public function getUsersForFilter(): \Illuminate\Database\Eloquent\Collection
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Mengambil data Trash gabungan dari seluruh tipe (atau 1 tipe jika
     * difilter) memakai UNION ALL di level database, lalu paginate hasilnya.
     * Setiap tabel selalu di-scope onlyTrashed() (WHERE deleted_at IS NOT NULL)
     * langsung dalam query, dan tidak pernah mengambil seluruh baris (get()).
     */
    public function getFilteredTrash(array $filters): LengthAwarePaginator
    {
        $requestedType = $filters['type'] ?? null;

        $types = ($requestedType && isset(self::TYPES[$requestedType]))
            ? [$requestedType => self::TYPES[$requestedType]]
            : self::TYPES;

        /** @var QueryBuilder|null $unioned */
        $unioned = null;

        foreach ($types as $key => $config) {
            $query = $this->buildTypeQuery($key, $config, $filters);

            $unioned = $unioned === null ? $query : $unioned->unionAll($query);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        $paginator = $unioned
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachDisplayMeta($paginator);

        return $paginator;
    }

    /**
     * Query dasar 1 tipe data: hanya kolom yang dibutuhkan (id, type, name,
     * deleted_at, deleted_by) supaya UNION ringan, dengan filter diterapkan
     * langsung di database (bukan di memori PHP).
     */
    private function buildTypeQuery(string $key, array $config, array $filters): QueryBuilder
    {
        $query = DB::table($config['table'])
            ->select([
                'id',
                DB::raw("'{$key}' as type"),
                "{$config['name_column']} as name",
                'deleted_at',
                'deleted_by',
            ])
            ->whereNotNull('deleted_at');

        if (!empty($filters['search'])) {
            $query->where($config['name_column'], 'like', '%' . trim($filters['search']) . '%');
        }

        if (!empty($filters['deleted_by'])) {
            $query->where('deleted_by', $filters['deleted_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('deleted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('deleted_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Menambahkan label tipe & nama user yang menghapus ke setiap baris hasil
     * paginasi. Memakai 1 query tambahan (whereIn) untuk seluruh baris pada
     * halaman ini, bukan query per-baris (mencegah N+1).
     */
    private function attachDisplayMeta(LengthAwarePaginator $paginator): void
    {
        $items = $paginator->getCollection();

        $userIds = $items->pluck('deleted_by')->filter()->unique()->values();

        $users = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $items->each(function ($row) use ($users) {
            $row->type_label = self::TYPES[$row->type]['label'] ?? ucfirst($row->type);
            $row->deleted_by_name = ($row->deleted_by && $users->has($row->deleted_by))
                ? $users->get($row->deleted_by)->name
                : null;
        });
    }

    /**
     * Memulihkan 1 data dari Trash. Dibungkus transaksi + row lock supaya
     * aman dari race condition (mis. 2 admin menekan tombol Pulihkan pada
     * data yang sama secara bersamaan, atau data sudah dipulihkan lebih dulu).
     *
     * @return array{id:int,type:string,name:string} Ringkasan data yang dipulihkan.
     */
    public function restore(string $type, int $id): array
    {
        $config = $this->config($type);
        $modelClass = $config['model'];

        return DB::transaction(function () use ($modelClass, $id, $type, $config) {
            $model = $modelClass::withTrashed()->lockForUpdate()->findOrFail($id);
            $name = (string) $model->{$config['name_column']};

            if (is_null($model->deleted_at)) {
                throw new RuntimeException(
                    "Data \"{$name}\" sudah tidak berada di Trash (kemungkinan sudah dipulihkan sebelumnya)."
                );
            }

            // deleted_by di-null-kan di sini karena restore() memanggil save(),
            // sehingga seluruh atribut yang sedang dirty (termasuk ini) ikut tersimpan.
            $model->deleted_by = null;
            $model->restore();

            $this->activityLogService->log(
                Auth::id(),
                'Trash',
                'Restore ' . $config['label']
            );

            return ['id' => $model->id, 'type' => $type, 'name' => $name];
        });
    }

    /**
     * Menghapus 1 data secara permanen (forceDelete). Hanya boleh dipanggil
     * setelah otorisasi Super Admin diverifikasi di layer Controller/Request.
     * Membersihkan berkas fisik terkait di storage sebelum baris dihapus,
     * karena FK cascade di database tidak menyentuh filesystem.
     *
     * @return array{id:int,type:string,name:string} Ringkasan data yang dihapus permanen.
     */
    public function forceDelete(string $type, int $id): array
    {
        $config = $this->config($type);
        $modelClass = $config['model'];

        return DB::transaction(function () use ($modelClass, $id, $type, $config) {
            $model = $modelClass::withTrashed()->lockForUpdate()->findOrFail($id);
            $name = (string) $model->{$config['name_column']};

            if (is_null($model->deleted_at)) {
                throw new RuntimeException(
                    "Data \"{$name}\" tidak berada di Trash sehingga tidak dapat dihapus permanen."
                );
            }

            $this->cleanupPhysicalFiles($model, $type);

            $model->forceDelete();

            $this->activityLogService->log(
                Auth::id(),
                'Trash',
                'Permanent Delete ' . $config['label']
            );

            return ['id' => $id, 'type' => $type, 'name' => $name];
        });
    }

    /**
     * Membersihkan berkas fisik di storage disk 'public' sebelum forceDelete,
     * supaya tidak menyisakan file yatim (orphan) di server.
     */
    private function cleanupPhysicalFiles(Model $model, string $type): void
    {
        switch ($type) {

            case 'inventory':
                foreach ([$model->image, $model->qr_code] as $path) {
                    if ($path && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
                break;

            case 'file':
                if ($model->file_path && Storage::disk('public')->exists($model->file_path)) {
                    Storage::disk('public')->delete($model->file_path);
                }
                break;

            case 'folder':
                // FK folder_id->files & parent_id->folders ber-onDelete('cascade')
                // di database, sehingga forceDelete folder ini otomatis ikut
                // menghapus baris subfolder & file (termasuk yang belum di-trash).
                // Berkas fisiknya harus dibersihkan lebih dulu secara rekursif.
                $this->cleanupFolderFilesRecursively($model);
                break;
        }
    }

    /**
     * Menghapus seluruh berkas fisik milik sebuah folder & subfolder-nya
     * (termasuk yang sudah trashed) dari storage disk, secara rekursif.
     */
    private function cleanupFolderFilesRecursively(Folder $folder): void
    {
        File::withTrashed()
            ->where('folder_id', $folder->id)
            ->get(['id', 'file_path'])
            ->each(function (File $file) {
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
            });

        Folder::withTrashed()
            ->where('parent_id', $folder->id)
            ->get(['id'])
            ->each(fn (Folder $subfolder) => $this->cleanupFolderFilesRecursively($subfolder));
    }

    /**
     * Validasi & ambil konfigurasi tipe data.
     */
    private function config(string $type): array
    {
        if (!isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Tipe data Trash tidak valid: {$type}");
        }

        return self::TYPES[$type];
    }
}

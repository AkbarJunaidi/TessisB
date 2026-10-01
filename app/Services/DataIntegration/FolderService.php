<?php

namespace App\Services\DataIntegration;

use App\Models\Folder;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FolderService
{
    /**
     * Service Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Service kunci (gembok) file & folder.
     */
    protected LockService $lockService;

    /**
     * Constructor.
     */
    public function __construct(
        ActivityLogService $activityLogService,
        LockService $lockService
    ) {
        $this->activityLogService = $activityLogService;
        $this->lockService        = $lockService;
    }

    /**
     * Membuat folder baru.
     *
     * Ruang bersama (Folder Management) : induk harus folder bersama (atau kosong = root bersama).
     * Ruang pribadi (My Files)          : $data['private'] = true; induk harus folder pribadi
     *                                     milik user ini (atau kosong = akar My Files).
     */
    public function createFolder(array $data): Folder
    {
        try {

            $private  = (bool) ($data['private'] ?? false);
            $parentId = $data['parent_id'] ?? null;

            if ($parentId) {
                $parent = Folder::find($parentId);

                if (!$parent) {
                    throw new Exception('Folder induk tidak ditemukan.');
                }

                if ($private) {
                    if (!$parent->is_private || !$this->isOwnedByCurrentUser($parent)) {
                        throw new Exception('Folder pribadi hanya dapat dibuat di dalam folder pribadi Anda.');
                    }
                } elseif ($parent->is_private) {
                    throw new Exception('Folder bersama tidak dapat dibuat di dalam folder pribadi.');
                }
            }

            $folder = Folder::create([
                'name'       => $data['name'],
                'parent_id'  => $parentId,
                'is_private' => $private,
                'created_by' => Auth::id(),
            ]);

            $this->activityLogService->log(
                Auth::id(),
                'Integrasi Data',
                $private ? 'Create Private Folder' : 'Create Folder'
            );

            return $folder;

        } catch (Exception $e) {
            throw new Exception(
                'Gagal membuat folder: ' . $e->getMessage()
            );
        }
    }

    /**
     * Rename folder.
     *
     * @throws Exception jika folder dikelola sistem (Document Center project).
     */
    public function renameFolder(
        Folder $folder,
        string $newName
    ): bool {

        $this->assertNotSystemFolder($folder);
        $this->lockService->assertCanChange($folder);

        return $folder->update([
            'name' => $newName
        ]);
    }

    /**
     * Memindahkan folder.
     *
     * Aturan:
     * - Tidak boleh ke dirinya sendiri atau ke salah satu sub-folder-nya (akan membuat putaran).
     * - Folder sistem (Document Center project) tidak boleh dipindah.
     * - Folder pribadi hanya ke folder pribadi milik sendiri (atau akar My Files bila target kosong);
     *   folder bersama hanya ke folder bersama (atau root bersama bila target kosong).
     *
     * @throws Exception
     */
    public function moveFolder(
        Folder $folder,
        ?int $targetFolderId
    ): bool {

        $this->assertNotSystemFolder($folder);
        $this->lockService->assertCanChange($folder);

        if ($targetFolderId !== null) {

            if ($targetFolderId === $folder->id
                || in_array($targetFolderId, $this->descendantIds($folder), true)) {
                throw new Exception(
                    'Folder tidak dapat dipindahkan ke dalam dirinya sendiri atau sub-folder-nya.'
                );
            }

            $target = Folder::find($targetFolderId);

            if (!$target) {
                throw new Exception('Folder tujuan tidak ditemukan.');
            }

            if ($folder->is_private) {
                if (!$target->is_private || !$this->isOwnedByCurrentUser($target)) {
                    throw new Exception('Folder pribadi hanya dapat dipindahkan ke folder pribadi Anda.');
                }
            } elseif ($target->is_private) {
                throw new Exception('Folder bersama tidak dapat dipindahkan ke folder pribadi.');
            }
        }

        return $folder->update([
            'parent_id' => $targetFolderId
        ]);
    }

    /**
     * Menghapus folder (Soft Delete).
     *
     * @throws Exception jika folder dikelola sistem (Document Center project).
     */
    public function deleteFolder(
        Folder $folder
    ): ?bool {

        $this->assertNotSystemFolder($folder);
        $this->lockService->assertCanChange($folder);
        $this->lockService->assertNoLockedContents($folder);

        // Catat siapa yang menghapus (dibaca oleh fitur Trash) sebelum soft delete,
        // karena SoftDeletes::delete() hanya menyimpan kolom deleted_at/updated_at.
        $folder->update(['deleted_by' => Auth::id()]);

        $deleted = $folder->delete();

        if ($deleted) {

            $this->activityLogService->log(
                Auth::id(),
                'Integrasi Data',
                $folder->is_private ? 'Delete Private Folder' : 'Delete Folder'
            );
        }

        return $deleted;
    }

    /**
     * Kunci folder bersama: folder beserta isinya tidak dapat diubah nama, dipindah, atau dihapus.
     *
     * @throws Exception
     */
    public function lockFolder(Folder $folder): void
    {
        if ($folder->is_private) {
            throw new Exception('Folder pribadi tidak perlu dikunci.');
        }

        if ($folder->isLocked()) {
            throw new Exception('Folder ini sudah terkunci.');
        }

        $this->lockService->lock($folder);

        $this->activityLogService->log(Auth::id(), 'Integrasi Data', 'Lock Folder');
    }

    /**
     * Buka kunci folder.
     *
     * @throws Exception
     */
    public function unlockFolder(Folder $folder): void
    {
        if (!$folder->isLocked()) {
            throw new Exception('Folder ini tidak dalam keadaan terkunci.');
        }

        $this->lockService->unlock($folder);

        $this->activityLogService->log(Auth::id(), 'Integrasi Data', 'Unlock Folder');
    }

    // ------------------------------------------------------------------
    //  Ruang pribadi (My Files) & bantuan struktur pohon
    // ------------------------------------------------------------------

    /**
     * Boleh MENGUBAH (rename/pindah/hapus) folder ini?
     * Folder pribadi hanya boleh diubah pemiliknya; folder bersama mengikuti permission modul.
     */
    public function canModify(Folder $folder): bool
    {
        return !$folder->is_private || $this->isOwnedByCurrentUser($folder);
    }

    /**
     * Ambil folder pribadi milik user yang login, atau null bila tidak ada / bukan miliknya.
     */
    public function findOwnPrivateFolder(int $folderId): ?Folder
    {
        return Folder::where('id', $folderId)
            ->where('is_private', true)
            ->where('created_by', Auth::id())
            ->first();
    }

    /**
     * Sub-folder pribadi milik user yang login di bawah $parentId (null = akar My Files).
     */
    public function getPrivateFolders(?int $parentId): \Illuminate\Database\Eloquent\Collection
    {
        return Folder::where('is_private', true)
            ->where('created_by', Auth::id())
            ->when(
                $parentId,
                fn ($q) => $q->where('parent_id', $parentId),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * Jejak folder dari akar ke $folder (untuk breadcrumb My Files), berurutan.
     *
     * @return Collection<int, Folder>
     */
    public function breadcrumb(Folder $folder): Collection
    {
        $tree  = $this->tree();
        $trail = collect();
        $id    = $folder->id;
        $guard = 0; // pengaman kalau ada data induk yang berputar

        while ($id && isset($tree[$id]) && $guard++ < 20) {
            $trail->prepend($tree[$id]);
            $id = $tree[$id]->parent_id;
        }

        return $trail;
    }

    /**
     * Peta id folder -> path lengkap ("Projects / Nama Project") untuk satu ruang:
     * $private = true  -> folder pribadi milik user yang login,
     * $private = false -> folder bersama.
     * Satu query lalu path dirakit di memori (hindari N+1).
     *
     * @return array<int,string>
     */
    public function pathMap(bool $private): array
    {
        $tree  = $this->tree()->filter(
            fn (Folder $f) => $private
                ? ($f->is_private && (int) $f->created_by === (int) Auth::id())
                : !$f->is_private
        );

        $paths = [];

        foreach ($tree as $folder) {
            $parts  = [$folder->name];
            $parent = $folder->parent_id;
            $guard  = 0;

            while ($parent && isset($tree[$parent]) && $guard++ < 20) {
                array_unshift($parts, $tree[$parent]->name);
                $parent = $tree[$parent]->parent_id;
            }

            $paths[$folder->id] = implode(' / ', $parts);
        }

        asort($paths, SORT_NATURAL | SORT_FLAG_CASE);

        return $paths;
    }

    /**
     * Semua id sub-folder (turunan) dari $folder, bertingkat.
     *
     * @return int[]
     */
    public function descendantIds(Folder $folder): array
    {
        $tree     = $this->tree();
        $byParent = $tree->groupBy('parent_id');
        $result   = [];
        $queue    = [$folder->id];

        while ($queue) {
            $current = array_shift($queue);

            foreach ($byParent->get($current, collect()) as $child) {
                if (!in_array($child->id, $result, true)) {
                    $result[] = $child->id;
                    $queue[]  = $child->id;
                }
            }
        }

        return $result;
    }

    /**
     * Folder "sistem" = folder Document Center sebuah project, atau folder yang di dalamnya
     * (bertingkat) memuat folder project - mis. induk "Projects". Folder ini dicari sistem
     * lewat relasi project / nama, jadi tidak boleh diganti nama, dipindah, atau dihapus.
     */
    public function isSystemFolder(Folder $folder): bool
    {
        if ($folder->project_id) {
            return true;
        }

        $tree = $this->tree();

        foreach ($this->descendantIds($folder) as $id) {
            if (isset($tree[$id]) && $tree[$id]->project_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Id semua folder sistem sekaligus (folder project + seluruh induknya) dalam satu kali
     * telusur - dipakai halaman daftar untuk menyembunyikan menu ubah/pindah/hapus tanpa N+1.
     *
     * @return int[]
     */
    public function systemFolderIds(): array
    {
        $tree = $this->tree();
        $ids  = [];

        foreach ($tree as $folder) {
            if (!$folder->project_id) {
                continue;
            }

            $id    = $folder->id;
            $guard = 0;

            while ($id && isset($tree[$id]) && $guard++ < 20) {
                $ids[$id] = true;
                $id = $tree[$id]->parent_id;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * @throws Exception
     */
    private function assertNotSystemFolder(Folder $folder): void
    {
        if ($this->isSystemFolder($folder)) {
            throw new Exception(
                'Folder ini dikelola sistem (Document Center project) sehingga tidak dapat diubah, dipindah, atau dihapus.'
            );
        }
    }

    private function isOwnedByCurrentUser(Folder $folder): bool
    {
        return (int) $folder->created_by === (int) Auth::id();
    }

    /**
     * Seluruh folder aktif (kolom ringkas), berkunci id. Satu query, dipakai bersama
     * oleh pembuat path, breadcrumb, dan pencari turunan.
     *
     * @return Collection<int, Folder>
     */
    private function tree(): Collection
    {
        return Folder::select('id', 'name', 'parent_id', 'project_id', 'is_private', 'created_by')
            ->get()
            ->keyBy('id');
    }

    /**
     * Mengambil folder khusus sebuah project (Document Center), atau membuatnya
     * otomatis jika belum ada. Struktur: root "Projects" > "<Nama Project>".
     * Dipanggil otomatis saat project baru dibuat (lihat ProjectService::createProject).
     */
    public function getOrCreateProjectFolder(\App\Models\Project $project): Folder
    {
        if ($project->folder) {
            return $project->folder;
        }

        $rootFolder = Folder::whereNull('parent_id')
            ->whereNull('project_id')
            ->where('is_private', false)
            ->where('name', 'Projects')
            ->first();

        if (!$rootFolder) {
            $rootFolder = Folder::create([
                'name'       => 'Projects',
                'parent_id'  => null,
                'created_by' => Auth::id(),
            ]);
        }

        return Folder::create([
            'name'       => $project->name,
            'parent_id'  => $rootFolder->id,
            'project_id' => $project->id,
            'created_by' => Auth::id(),
        ]);
    }
}

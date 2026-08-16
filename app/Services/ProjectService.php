<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\SuratJalanItem;
use App\Models\InventoryUnit;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\DataIntegration\FolderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    protected ActivityLogService $activityLogService;
    protected FolderService $folderService;

    public function __construct(ActivityLogService $activityLogService, FolderService $folderService)
    {
        $this->activityLogService = $activityLogService;
        $this->folderService = $folderService;
    }

//  Mengambil data project dengan pagination + filter (search, status, pic, bulan, tanggal).
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Project::with('creator', 'suratJalans');

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('pic', 'like', "%{$keyword}%")
                  ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'Semua Status') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['pic']) && $filters['pic'] !== 'Semua PIC') {
            $query->where('pic', $filters['pic']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('event_date', $filters['month']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('event_date', $filters['date']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

// Statistik ringkas untuk dashboard Project Management.
    public function getStats(): array
    {
        return [
            'total'      => Project::count(),
            'today'      => Project::whereDate('event_date', now()->toDateString())->count(),
            'in_progress' => Project::where('status', 'In Progress')->count(),
            'done'       => Project::where('status', 'Done')->count(),
        ];
    }

// Data untuk kalender project pada bulan & tahun tertentu (jumlah project per tanggal).
    public function getCalendarData(int $month, int $year): array
    {
        return Project::whereMonth('event_date', $month)
            ->whereYear('event_date', $year)
            ->whereNotNull('event_date')
            ->get()
            ->groupBy(fn ($project) => $project->event_date->format('Y-m-d'))
            ->map->count()
            ->toArray();
    }

// Daftar PIC unik untuk dropdown filter
    public function getDistinctPics(): array
    {
        return Project::whereNotNull('pic')->distinct()->orderBy('pic')->pluck('pic')->toArray();
    }

// Menambahkan project baru.
    public function createProject(array $data): Project
    {
        $project = Project::create([
            'name'                        => $data['name'],
            'client'                      => $data['client'],
            'pic'                         => $data['pic'],
            'category'                    => $data['category'],
            'description'                 => $data['description'] ?? null,
            'deadline'                    => $data['deadline'],
            'event_date'                  => $data['event_date'],
            'event_time_start'            => $data['event_time_start'],
            'event_time_end'              => $data['event_time_end'] ?? null,
            'location'                    => $data['location'],
            'address'                     => $data['address'],
            'estimated_duration_minutes'  => $data['estimated_duration_minutes'],
            'priority'                    => $data['priority'],
            'created_by'                  => Auth::id(),
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Create Project'
        );

        // Document Center: buat folder khusus project secara otomatis (PRD Bagian 9).
        $this->folderService->getOrCreateProjectFolder($project);

        return $project;
    }

// Memperbarui data project yang sudah ada.
    public function updateProject(Project $project, array $data): Project
    {
        $project->update([
            'name'                        => $data['name'],
            'client'                      => $data['client'],
            'pic'                         => $data['pic'],
            'category'                    => $data['category'],
            'description'                 => $data['description'] ?? null,
            'deadline'                    => $data['deadline'],
            'event_date'                  => $data['event_date'],
            'event_time_start'            => $data['event_time_start'],
            'event_time_end'              => $data['event_time_end'] ?? null,
            'location'                    => $data['location'],
            'address'                     => $data['address'],
            'estimated_duration_minutes'  => $data['estimated_duration_minutes'],
            'priority'                    => $data['priority'],
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Update Project'
        );

        return $project;
    }

    /**
     * Mengecek status pengembalian barang (Surat Jalan) milik project ini.
     * Dipakai pada popup konfirmasi Hapus Project agar admin tahu jika masih
     * ada barang inventory yang belum dikembalikan sebelum project dihapus.
     *
     * @return array{fully_returned: bool, items: array<int, array{inventory_name:string, qty_belum_kembali:int, surat_jalan_nomor:string}>}
     */
    public function getUnreturnedInventoryItems(Project $project): array
    {
        $unreturnedItems = SuratJalanItem::query()
            ->select(['id', 'surat_jalan_id', 'inventory_id', 'qty_dipakai', 'qty_dikembalikan'])
            ->whereColumn('qty_dipakai', '>', 'qty_dikembalikan')
            ->whereHas('suratJalan', fn ($query) => $query->where('project_id', $project->id))
            ->with([
                'inventory:id,name',
                'suratJalan:id,nomor',
            ])
            ->get();

        $items = $unreturnedItems->map(fn (SuratJalanItem $item) => [
            'inventory_name'    => $item->inventory->name ?? 'Barang tidak ditemukan',
            'qty_belum_kembali' => $item->qty_dipakai - $item->qty_dikembalikan,
            'surat_jalan_nomor' => $item->suratJalan->nomor ?? '-',
        ])->values()->all();

        return [
            'fully_returned' => empty($items),
            'items'          => $items,
        ];
    }

//  Menghapus project (Soft Delete).
    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {

            // Barang yang masih dipinjam (belum dikembalikan) lewat Surat Jalan
            // project ini tidak akan pernah bisa dikembalikan lagi lewat alur
            // normal setelah project-nya hilang dari daftar, jadi unit fisiknya
            // ditandai "Hilang" di Inventory supaya stok tidak menggantung
            // selamanya berstatus "Dipinjam" tanpa project yang jelas.
            $this->markUnreturnedUnitsAsLost($project);

            // Catat siapa yang menghapus (dibaca oleh fitur Trash) sebelum soft delete,
            // karena SoftDeletes::delete() hanya menyimpan kolom deleted_at/updated_at.
            $project->update(['deleted_by' => Auth::id()]);

            $deleted = $project->delete();

            if ($deleted) {
                $this->activityLogService->log(
                    Auth::id(),
                    'Tracking Progress',
                    'Delete Project'
                );
            }

            return $deleted;
        });
    }
//untuk menandai unit inventory yang belum dikembalikan sebagai "Hilang" saat project dihapus.
    private function markUnreturnedUnitsAsLost(Project $project): int
    {
        $unreturnedItems = SuratJalanItem::query()
            ->whereColumn('qty_dipakai', '>', 'qty_dikembalikan')
            ->whereHas('suratJalan', fn ($query) => $query->where('project_id', $project->id))
            ->get(['id', 'surat_jalan_id', 'qty_dipakai', 'qty_dikembalikan']);

        if ($unreturnedItems->isEmpty()) {
            return 0;
        }

        $totalUnitsLost = 0;
        $affectedSuratJalanIds = [];

        foreach ($unreturnedItems as $item) {
            $unitIds = InventoryUnit::where('surat_jalan_item_id', $item->id)->pluck('id');

            if ($unitIds->isNotEmpty()) {
                InventoryUnit::whereIn('id', $unitIds)->update([
                    'status' => 'Hilang',
                    'surat_jalan_item_id' => null,
                ]);

                $totalUnitsLost += $unitIds->count();
            }

            // Tutup sisa qty yang belum kembali sebagai selesai/resolved (hilang),
            // supaya qty_in_use & qty_available di Inventory tetap akurat.
            $item->update(['qty_dikembalikan' => $item->qty_dipakai]);

            $affectedSuratJalanIds[$item->surat_jalan_id] = true;
        }

        // Kalau seluruh item pada Surat Jalan terkait kini sudah resolved
        // (kembali normal ATAU ditutup sebagai hilang), tandai Selesai supaya
        // Surat Jalan tidak menggantung berstatus "Aktif" untuk project yang
        // sudah tidak ada lagi.
        SuratJalan::whereIn('id', array_keys($affectedSuratJalanIds))
            ->whereDoesntHave('items', fn ($q) => $q->whereColumn('qty_dipakai', '>', 'qty_dikembalikan'))
            ->where('status', '!=', 'Selesai')
            ->update(['status' => 'Selesai']);

        return $totalUnitsLost;
    }
}

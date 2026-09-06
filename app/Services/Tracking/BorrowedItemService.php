<?php

namespace App\Services\Tracking;

use App\Models\InventoryUnit;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class BorrowedItemService
{
    public function __construct(
        protected SuratJalanService $suratJalanService
    ) {}

    /**
     * Seluruh project yang masih punya barang dipinjam (ada SuratJalanItem
     * dengan qty_dikembalikan < qty_dipakai). Project yang seluruh barangnya
     * sudah kembali otomatis tidak ikut muncul di sini.
     */
    public function getProjectsWithBorrowedItems(): Collection
    {
        return Project::whereHas(
            'suratJalans.items',
            fn ($q) => $q->whereColumn('qty_dipakai', '>', 'qty_dikembalikan')
        )
            ->with([
                'suratJalans' => fn ($q) => $q->whereHas(
                    'items',
                    fn ($q2) => $q2->whereColumn('qty_dipakai', '>', 'qty_dikembalikan')
                ),
                'suratJalans.items' => fn ($q) => $q->whereColumn('qty_dipakai', '>', 'qty_dikembalikan'),
            ])
            ->latest()
            ->get();
    }

    /**
     * Jumlah unit barang yang masih dipinjam untuk 1 project (dihitung dari
     * relasi yang sudah di-eager-load di getProjectsWithBorrowedItems(),
     * supaya tidak query tambahan per project / anti N+1).
     */
    public function countBorrowedUnits(Project $project): int
    {
        return $project->suratJalans
            ->flatMap(fn ($sj) => $sj->items)
            ->sum(fn ($item) => $item->qty_dipakai - $item->qty_dikembalikan);
    }

    /**
     * Seluruh unit fisik yang sedang dipinjam untuk BANYAK project sekaligus
     * (1 query), dikelompokkan per project_id. Dipakai saat render halaman index
     * supaya tidak N+1 (query per-project satu-satu).
     */
    public function getBorrowedUnitsGroupedByProject(Collection $projects): Collection
    {
        $projectIds = $projects->pluck('id');

        return InventoryUnit::whereNotNull('surat_jalan_item_id')
            ->whereHas(
                'suratJalanItem.suratJalan',
                fn ($q) => $q->whereIn('project_id', $projectIds)
            )
            ->with(['inventory', 'suratJalanItem.suratJalan'])
            ->orderBy('inventory_id')
            ->orderBy('unit_number')
            ->get()
            ->groupBy(fn ($unit) => $unit->suratJalanItem->suratJalan->project_id);
    }

    /**
     * Seluruh unit fisik yang sedang dipinjam untuk 1 project, lengkap dengan
     * info barang & nomor Surat Jalan-nya - dipakai untuk refresh AJAX setelah
     * konfirmasi pengembalian (1 project saja, jadi query tunggal tidak masalah).
     */
    public function getBorrowedUnitsForProject(Project $project): Collection
    {
        return InventoryUnit::whereNotNull('surat_jalan_item_id')
            ->whereHas(
                'suratJalanItem.suratJalan',
                fn ($q) => $q->where('project_id', $project->id)
            )
            ->with(['inventory', 'suratJalanItem.suratJalan'])
            ->orderBy('inventory_id')
            ->orderBy('unit_number')
            ->get();
    }

    /**
     * Proses konfirmasi pengembalian sejumlah unit yang dipilih user di UI.
     * Memvalidasi seluruh unit benar-benar milik project ini & sedang dipinjam,
     * lalu mendelegasikan proses inti (transaction, update stok, dst) ke
     * SuratJalanService supaya logic pengembalian tetap satu sumber.
     *
     * @param  array<int>  $unitIds
     * @throws Exception
     */
    public function returnUnits(Project $project, array $unitIds): array
    {
        $units = InventoryUnit::whereIn('id', $unitIds)
            ->whereNotNull('surat_jalan_item_id')
            ->with('suratJalanItem.suratJalan')
            ->get();

        if ($units->isEmpty()) {
            throw new Exception('Tidak ada unit valid yang dipilih untuk dikembalikan.');
        }

        $invalidUnit = $units->first(
            fn ($unit) => !$unit->suratJalanItem
                || !$unit->suratJalanItem->suratJalan
                || $unit->suratJalanItem->suratJalan->project_id !== $project->id
        );

        if ($invalidUnit) {
            throw new Exception('Salah satu unit yang dipilih tidak berasal dari project ini atau sudah dikembalikan.');
        }

        return $this->suratJalanService->returnUnitsForProject($project, $units);
    }
}

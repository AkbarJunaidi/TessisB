<?php

namespace App\Services\Inventory;

use App\Models\AppSetting;
use App\Models\ProjectFinanceItem;
use App\Support\FinanceCategory;
use App\Support\FinanceLock;
use App\Models\Inventory;
use App\Models\InventoryRepair;
use App\Models\InventoryRepairItem;
use App\Models\InventoryUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan Barang: unit dikirim ke tempat servis (vendor / toko / bengkel), berstatus
 * "Perbaikan" selama di sana, lalu kembali Tersedia atau Rusak saat perbaikan diselesaikan.
 */
class RepairService
{
    /** Biaya servis satu unit di atas rasio ini terhadap harga beli baru dianggap layak diganti (Pengaturan). */
    public static function replaceWarnRatio(): float
    {
        return AppSetting::int('repair_warn_percent') / 100;
    }

    /** Rasio mulai dari sini ditandai "pantau" di Daftar Harga Barang: 60% dari ambang ganti. */
    public static function replaceWatchRatio(): float
    {
        return round(self::replaceWarnRatio() * 0.6, 2);
    }

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryMutationService $mutationService
    ) {}

    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryRepair::with(['vendor:id,name', 'items.inventory:id,name', 'items.unit:id,unit_number'])
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(function ($w) use ($keyword) {
                    $w->where('code', 'like', "%{$keyword}%")
                        ->orWhere('tempat_nama', 'like', "%{$keyword}%")
                        ->orWhereHas('items.inventory', fn ($i) => $i->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [InventoryRepair::STATUS_ACTIVE])
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{aktif: int, unit_aktif: int, terlambat: int, biaya_bulan_ini: float}
     */
    public function getStats(): array
    {
        return [
            'aktif'      => InventoryRepair::active()->count(),
            'unit_aktif' => InventoryRepairItem::whereHas('repair', fn ($q) => $q->active())->count(),
            'terlambat'  => InventoryRepair::active()
                ->whereNotNull('estimasi_selesai')
                ->whereDate('estimasi_selesai', '<', today())
                ->count(),
            'biaya_bulan_ini' => (float) InventoryRepair::where('status', InventoryRepair::STATUS_DONE)
                ->whereYear('tanggal_selesai', now()->year)
                ->whereMonth('tanggal_selesai', now()->month)
                ->sum('biaya'),
        ];
    }

    /**
     * Barang yang punya unit boleh dikirim servis: tidak dipinjam, tidak hilang,
     * dan belum tercatat sedang diservis. Hanya unit yang memenuhi syarat yang ikut dimuat.
     */
    public function getRepairableInventories(): Collection
    {
        $eligible = fn ($q) => $q->where('status', '!=', 'Hilang')
            ->whereNull('surat_jalan_item_id')
            ->whereNull('repair_item_id');

        return Inventory::whereHas('units', $eligible)
            ->with(['units' => fn ($q) => $eligible($q)])
            ->orderBy('name')
            ->get(['id', 'name', 'serial_number', 'servis_interval_hari', 'servis_interval_pemakaian']);
    }

    /**
     * Mencatat unit yang dikirim ke tempat servis dan mengubah statusnya menjadi Perbaikan.
     *
     * @throws Exception
     */
    public function create(array $data): InventoryRepair
    {
        return DB::transaction(function () use ($data) {
            $unitIds = array_values(array_unique($data['unit_ids']));

            $units = InventoryUnit::whereIn('id', $unitIds)
                ->lockForUpdate()
                ->with('inventory:id,name')
                ->get();

            if ($units->count() !== count($unitIds)) {
                throw new Exception('Sebagian unit yang dipilih tidak ditemukan.');
            }

            foreach ($units as $unit) {
                $label = "Unit #{$unit->unit_number} ({$unit->inventory->name})";

                if ($unit->surat_jalan_item_id) {
                    throw new Exception("{$label} sedang dipinjam - kirim ke servis setelah dikembalikan.");
                }

                if ($unit->repair_item_id) {
                    throw new Exception("{$label} sudah tercatat sedang diservis.");
                }

                if ($unit->status === 'Hilang') {
                    throw new Exception("{$label} berstatus Hilang.");
                }
            }

            $repair = InventoryRepair::create([
                'code'             => $this->generateCode(),
                'vendor_id'        => $data['vendor_id'] ?? null,
                'tempat_nama'      => $data['tempat_nama'],
                'tempat_alamat'    => $data['tempat_alamat'] ?? null,
                'status'           => InventoryRepair::STATUS_ACTIVE,
                'tanggal_masuk'    => $data['tanggal_masuk'],
                'estimasi_selesai' => $data['estimasi_selesai'] ?? null,
                'diantar_oleh'     => $data['diantar_oleh'] ?? null,
                'keluhan'          => $data['keluhan'] ?? null,
                'created_by'       => Auth::id(),
            ]);

            foreach ($units as $unit) {
                $item = $repair->items()->create([
                    'inventory_id'      => $unit->inventory_id,
                    'inventory_unit_id' => $unit->id,
                    'status_sebelum'    => $unit->status,
                ]);

                $unit->update(['status' => 'Perbaikan', 'repair_item_id' => $item->id]);
            }

            foreach ($units->groupBy('inventory_id') as $inventoryId => $group) {
                $this->mutationService->record(
                    (int) $inventoryId,
                    'masuk_servis',
                    $group->count(),
                    null,
                    $this->unitNumbers($group) . " - dikirim ke {$repair->tempat_nama} ({$repair->code})"
                );
            }

            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                "Mencatat perbaikan {$repair->code}: {$this->itemSummary($units)} di {$repair->tempat_nama}"
            );

            return $repair;
        });
    }

    /**
     * Tiap unit kembali Tersedia (jadwal servis dihitung ulang) atau Rusak bila gagal diperbaiki.
     *
     * @throws Exception
     */
    public function complete(InventoryRepair $repair, array $data): InventoryRepair
    {
        return DB::transaction(function () use ($repair, $data) {
            $repair = InventoryRepair::whereKey($repair->id)->lockForUpdate()->firstOrFail();

            if (!$repair->isActive()) {
                throw new Exception('Perbaikan ini sudah tidak berjalan.');
            }

            if ($repair->tanggal_masuk->gt($data['tanggal_selesai'])) {
                throw new Exception('Tanggal selesai tidak boleh sebelum tanggal barang masuk servis.');
            }

            if ((float) ($data['biaya'] ?? 0) > 0) {
                FinanceLock::assertOpen($data['tanggal_selesai']);
            }

            $items   = $repair->items()->with(['unit', 'inventory'])->get();
            $hasil   = $data['hasil'] ?? [];
            $tersedia = collect();
            $rusak    = collect();

            foreach ($items as $item) {
                $result = ($hasil[$item->id] ?? 'Tersedia') === 'Rusak' ? 'Rusak' : 'Tersedia';
                $unit   = $item->unit;

                // Hanya unit yang masih menunjuk ke item ini yang disentuh.
                if ($unit && (int) $unit->repair_item_id === (int) $item->id) {
                    $update = ['status' => $result, 'repair_item_id' => null];

                    if ($result === 'Tersedia' && $item->inventory?->punyaJadwalServis()) {
                        $update += ['servis_terakhir_at' => $data['tanggal_selesai'], 'pemakaian_sejak_servis' => 0];
                    }

                    $unit->update($update);
                }

                $item->update(['hasil' => $result]);
                ($result === 'Tersedia' ? $tersedia : $rusak)->push($item);
            }

            $biaya = $data['biaya'] ?? null;

            $repair->update([
                'status'          => InventoryRepair::STATUS_DONE,
                'tanggal_selesai' => $data['tanggal_selesai'],
                'diambil_oleh'    => $data['diambil_oleh'] ?? null,
                'catatan_hasil'   => $data['catatan_hasil'] ?? null,
                'biaya'           => ($biaya === null || $biaya === '') ? null : $biaya,
                'completed_by'    => Auth::id(),
            ]);

            // Biaya servis otomatis masuk buku kas sebagai pengeluaran non-project.
            if ((float) $repair->biaya > 0) {
                ProjectFinanceItem::create([
                    'project_id'  => null,
                    'type'        => 'expense',
                    'amount'      => $repair->biaya,
                    'description' => "Perbaikan {$repair->code} - {$repair->tempat_nama}",
                    'tanggal'     => $data['tanggal_selesai'],
                    'category'    => FinanceCategory::REPAIR,
                    'contact_id'  => $repair->vendor_id,
                    'source_type' => ProjectFinanceItem::SOURCE_REPAIR,
                    'source_id'   => $repair->id,
                    'created_by'  => Auth::id(),
                ]);
            }

            foreach ($tersedia->groupBy('inventory_id') as $inventoryId => $group) {
                $this->mutationService->record(
                    (int) $inventoryId,
                    'servis_selesai',
                    $group->count(),
                    null,
                    $this->itemNumbers($group) . " - selesai diservis di {$repair->tempat_nama} ({$repair->code})"
                );
            }

            foreach ($rusak as $item) {
                $this->mutationService->recordStatusChange($item->inventory_id, 'Perbaikan', 'Rusak');
            }

            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                "Perbaikan {$repair->code} selesai di {$repair->tempat_nama}: {$tersedia->count()} unit kembali Tersedia, {$rusak->count()} unit Rusak"
            );

            return $repair->fresh();
        });
    }

    /**
     * Membatalkan catatan yang salah input: unit kembali ke status sebelum dikirim.
     *
     * @throws Exception
     */
    public function cancel(InventoryRepair $repair): InventoryRepair
    {
        return DB::transaction(function () use ($repair) {
            $repair = InventoryRepair::whereKey($repair->id)->lockForUpdate()->firstOrFail();

            if (!$repair->isActive()) {
                throw new Exception('Perbaikan ini sudah tidak berjalan.');
            }

            $items = $repair->items()->with('unit')->get();

            foreach ($items as $item) {
                $unit = $item->unit;

                if ($unit && (int) $unit->repair_item_id === (int) $item->id) {
                    $unit->update(['status' => $item->status_sebelum, 'repair_item_id' => null]);
                }
            }

            $repair->update(['status' => InventoryRepair::STATUS_CANCELLED]);

            foreach ($items->groupBy('inventory_id') as $inventoryId => $group) {
                $this->mutationService->record(
                    (int) $inventoryId,
                    'servis_dibatalkan',
                    $group->count(),
                    null,
                    $this->itemNumbers($group) . " - catatan perbaikan {$repair->code} dibatalkan"
                );
            }

            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                "Membatalkan catatan perbaikan {$repair->code} ({$repair->tempat_nama})"
            );

            return $repair->fresh();
        });
    }

    /**
     * Perbandingan servis vs beli baru per barang untuk form Pembelian.
     * Biaya catatan berisi beberapa unit dibagi rata per unit (perkiraan).
     *
     * @return array<int, array{last_price: ?float, last_code: ?string, last_purchase_id: ?int, servis_total: float, servis_count: int, worst_unit: ?array{number: int, total: float}, units: array<int, array{number: int, total: float}>}>
     */
    public function costComparison(): array
    {
        $shares = DB::table('inventory_repair_items as i')
            ->join('inventory_repairs as r', 'r.id', '=', 'i.repair_id')
            ->join('inventory_units as u', 'u.id', '=', 'i.inventory_unit_id')
            ->joinSub(
                DB::table('inventory_repair_items')->select('repair_id', DB::raw('COUNT(*) as n'))->groupBy('repair_id'),
                'c',
                'c.repair_id',
                '=',
                'i.repair_id'
            )
            ->where('r.status', InventoryRepair::STATUS_DONE)
            ->where('r.biaya', '>', 0)
            ->selectRaw('i.inventory_id, i.inventory_unit_id, u.unit_number, r.id as repair_id, r.biaya / c.n as share')
            ->get();

        $empty = ['last_price' => null, 'last_code' => null, 'last_purchase_id' => null, 'servis_total' => 0.0, 'servis_count' => 0, 'worst_unit' => null, 'units' => []];
        $map = [];

        foreach ($shares->groupBy('inventory_id') as $inventoryId => $rows) {
            $perUnit = $rows->groupBy('inventory_unit_id')
                ->map(fn ($g) => ['number' => (int) $g->first()->unit_number, 'total' => round((float) $g->sum('share'), 2)]);

            $map[(int) $inventoryId] = array_merge($empty, [
                'servis_total' => round((float) $rows->sum('share'), 2),
                'servis_count' => $rows->pluck('repair_id')->unique()->count(),
                'worst_unit'   => $perUnit->sortByDesc('total')->first(),
                'units'        => $perUnit->all(),
            ]);
        }

        $prices = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', Purchase::STATUS_RECEIVED)
            ->whereNotNull('purchase_items.inventory_id')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->get(['purchase_items.inventory_id', 'purchase_items.unit_price', 'purchases.code', 'purchases.id as purchase_id']);

        foreach ($prices->unique('inventory_id') as $row) {
            $map[(int) $row->inventory_id] = array_merge($map[(int) $row->inventory_id] ?? $empty, [
                'last_price' => (float) $row->unit_price,
                'last_code'  => $row->code,
                'last_purchase_id' => (int) $row->purchase_id,
            ]);
        }

        return $map;
    }

    /**
     * Daftar harga barang: harga beli terakhir berdampingan dengan biaya servisnya.
     *
     * @param array{search?: ?string, filter?: ?string} $filters filter: servis | ganti
     * @return array{items: LengthAwarePaginator, costs: array, summary: array<string, int|float>}
     */
    public function getPriceList(array $filters = [], int $perPage = 20): array
    {
        $costs = $this->costComparison();
        $activeIds = Inventory::pluck('id')->all();
        $costs = array_intersect_key($costs, array_flip($activeIds));

        $filterIds = match ($filters['filter'] ?? null) {
            'servis' => array_keys(array_filter($costs, fn ($c) => $c['servis_count'] > 0)),
            'ganti'  => array_keys(array_filter($costs, fn ($c) => (self::replaceRatio($c) ?? 0) >= self::replaceWarnRatio())),
            default  => null,
        };

        $items = Inventory::withCount('units')
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(fn ($w) => $w->where('name', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%")
                    ->orWhere('serial_number', 'like', "%{$keyword}%"));
            })
            ->when($filterIds !== null, fn ($q) => $q->whereIn('id', $filterIds))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'total_barang' => count($activeIds),
            'ada_harga'    => count(array_filter($costs, fn ($c) => $c['last_price'] !== null)),
            'total_servis' => round(array_sum(array_column($costs, 'servis_total')), 2),
            'layak_ganti'  => count(array_filter($costs, fn ($c) => (self::replaceRatio($c) ?? 0) >= self::replaceWarnRatio())),
        ];

        return ['items' => $items, 'costs' => $costs, 'summary' => $summary];
    }

    /**
     * Biaya servis unit paling mahal dibanding harga beli terakhir; null bila salah satunya belum ada.
     */
    public static function replaceRatio(array $cost): ?float
    {
        if (empty($cost['worst_unit']) || empty($cost['last_price'])) {
            return null;
        }

        return $cost['worst_unit']['total'] / $cost['last_price'];
    }

    protected function generateCode(): string
    {
        $year = now()->format('Y');

        $last = InventoryRepair::where('code', 'like', "PRB-{$year}-%")
            ->orderByDesc('id')
            ->value('code');

        return sprintf('PRB-%s-%04d', $year, $last ? ((int) substr($last, -4)) + 1 : 1);
    }

    /** "Unit #1, #3" dari kumpulan InventoryUnit. */
    protected function unitNumbers(Collection $units): string
    {
        return 'Unit ' . $units->sortBy('unit_number')->map(fn ($u) => '#' . $u->unit_number)->implode(', ');
    }

    /** "Unit #1, #3" dari kumpulan InventoryRepairItem (relasi unit sudah dimuat). */
    protected function itemNumbers(Collection $items): string
    {
        return 'Unit ' . $items->map(fn ($i) => '#' . ($i->unit?->unit_number ?? '?'))->sort()->implode(', ');
    }

    /** "Kamera A x2, Tripod x1" untuk log aktivitas. */
    protected function itemSummary(Collection $units): string
    {
        return $units->groupBy('inventory_id')
            ->map(fn ($group) => ($group->first()->inventory->name ?? 'Barang') . ' x' . $group->count())
            ->implode(', ');
    }
}

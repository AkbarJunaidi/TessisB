<?php

namespace App\Services\Inventory;

use App\Support\PerPage;
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

    /** Rasio mulai dari sini ditandai "pantau" di Daftar Harga Barang: 60% dari ambang ganti barang itu. */
    public static function replaceWatchRatio(?float $warn = null): float
    {
        return round(($warn ?? self::replaceWarnRatio()) * 0.6, 2);
    }

    /** Servis dalam periode dibanding jumlah unit; salah satu syarat barang ditandai rawan rusak (Pengaturan). */
    public static function rawanRatio(): float
    {
        return AppSetting::int('repair_rawan_percent') / 100;
    }

    /** Periode hitung rawan rusak dalam hari (Pengaturan). */
    public static function rawanDays(): int
    {
        return AppSetting::int('repair_rawan_hari');
    }

    /** Jumlah servis dalam periode yang menandai barang rawan rusak (Pengaturan). */
    public static function rawanTimes(): int
    {
        return AppSetting::int('repair_rawan_kali');
    }

    /** Unit gagal dibanding yang pernah diservis; dari sini barang ditandai sulit diperbaiki (Pengaturan). */
    public static function gagalRatio(): float
    {
        return AppSetting::int('repair_gagal_percent') / 100;
    }

    /** Ambang ganti satu barang: isian khusus barang, atau Pengaturan bila kosong. */
    public static function warnRatioFor(?int $ambang): float
    {
        return $ambang !== null ? $ambang / 100 : self::replaceWarnRatio();
    }

    /** Bentuk dasar perbandingan biaya untuk barang yang belum punya harga maupun servis. */
    public static function emptyCost(?int $ambang = null): array
    {
        return [
            'last_price' => null, 'last_date' => null, 'last_code' => null, 'last_purchase_id' => null, 'price_source' => null,
            'servis_total' => 0.0, 'servis_count' => 0, 'servis_estimasi' => false, 'worst_unit' => null, 'units' => [],
            'warn_ratio' => self::warnRatioFor($ambang),
        ];
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
            ->paginate(PerPage::resolve($perPage))
            ->withQueryString();
    }

    /**
     * Kondisi barang: satu baris per barang yang pernah dikirim servis (catatan dibatalkan tidak dihitung).
     * Diservis = kejadian servis yang sudah selesai (unit x surat). Penanda: rawan (servis dalam periode terakhir), sulit,
     * mahal (butuh izin Pembelian), atau aman.
     *
     * @param array{search?: ?string, penanda?: ?string, inventory_id?: int|string|null} $filters
     */
    public function getCondition(array $filters = [], bool $withCost = false, int $perPage = 10): LengthAwarePaginator
    {
        $stats = DB::table('inventory_repair_items as i')
            ->join('inventory_repairs as r', 'r.id', '=', 'i.repair_id')
            ->where('r.status', '!=', InventoryRepair::STATUS_CANCELLED)
            ->groupBy('i.inventory_id')
            ->selectRaw("i.inventory_id,
                SUM(CASE WHEN i.hasil IS NOT NULL THEN 1 ELSE 0 END) as diservis,
                SUM(CASE WHEN i.hasil = 'Tersedia' THEN 1 ELSE 0 END) as berhasil,
                SUM(CASE WHEN i.hasil = 'Rusak' THEN 1 ELSE 0 END) as gagal,
                SUM(CASE WHEN i.hasil IS NULL THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN i.hasil IS NOT NULL AND r.tanggal_selesai >= ? THEN 1 ELSE 0 END) as periode,
                MAX(CASE WHEN i.hasil IS NOT NULL THEN r.tanggal_selesai END) as terakhir", [now()->subDays(self::rawanDays())->toDateString()])
            ->get()
            ->keyBy('inventory_id');

        $costs = $withCost ? $this->costComparison() : [];

        $rows = Inventory::withCount('units')
            ->whereIn('id', $stats->keys()->all())
            ->when(!empty($filters['inventory_id']), fn ($q) => $q->where('id', (int) $filters['inventory_id']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(fn ($w) => $w->where('name', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%")
                    ->orWhere('serial_number', 'like', "%{$keyword}%"));
            })
            ->get()
            ->map(function (Inventory $inventory) use ($stats, $costs) {
                $stat     = $stats[$inventory->id];
                $diservis = (int) $stat->diservis;
                $gagal    = (int) $stat->gagal;
                $units    = max((int) $inventory->units_count, 1);
                $cost     = $costs[$inventory->id] ?? null;
                $ratio    = $cost ? self::replaceRatio($cost) : null;
                $flags    = [];

                $periode = (int) $stat->periode;

                if ($periode >= 2 && ($periode >= self::rawanTimes() || $periode / $units >= self::rawanRatio())) {
                    $flags[] = 'rawan';
                }

                if ($diservis >= 2 && $gagal > 0 && $gagal / $diservis >= self::gagalRatio()) {
                    $flags[] = 'sulit';
                }

                if ($ratio !== null && $ratio >= $cost['warn_ratio']) {
                    $flags[] = 'mahal';
                }

                return (object) [
                    'inventory'    => $inventory,
                    'units'        => (int) $inventory->units_count,
                    'diservis'     => $diservis,
                    'berhasil'     => (int) $stat->berhasil,
                    'periode'      => $periode,
                    'gagal'        => $gagal,
                    'aktif'        => (int) $stat->aktif,
                    'persen_berhasil' => $diservis > 0 ? (int) round((int) $stat->berhasil / $diservis * 100) : null,
                    'persen_gagal' => $diservis > 0 ? (int) round($gagal / $diservis * 100) : null,
                    'terakhir'     => $stat->terakhir ? \Carbon\Carbon::parse($stat->terakhir) : null,
                    'flags'        => $flags,
                    'ratio'        => $ratio,
                    'cost'         => $cost,
                ];
            });

        $penanda = $filters['penanda'] ?? null;

        $rows = $rows
            ->when($penanda === 'aman', fn ($c) => $c->filter(fn ($r) => $r->diservis > 0 && $r->flags === []))
            ->when(in_array($penanda, ['rawan', 'sulit', 'mahal'], true), fn ($c) => $c->filter(fn ($r) => in_array($penanda, $r->flags, true)))
            ->sortBy([
                fn ($a, $b) => count($b->flags) <=> count($a->flags),
                fn ($a, $b) => $b->gagal <=> $a->gagal,
                fn ($a, $b) => $b->diservis <=> $a->diservis,
                fn ($a, $b) => strcasecmp($a->inventory->name, $b->inventory->name),
            ])
            ->values();

        $perPage = PerPage::resolve($perPage);
        $page    = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => request()->url()]))
            ->withQueryString();
    }

    /**
     * Rincian kondisi satu barang: surat perbaikan terbaru lebih dulu, hanya unit barang itu yang dimuat.
     *
     * @return Collection<int, InventoryRepair>
     */
    public function getConditionDetail(Inventory $inventory, int $limit = 30): Collection
    {
        return InventoryRepair::where('status', '!=', InventoryRepair::STATUS_CANCELLED)
            ->whereHas('items', fn ($q) => $q->where('inventory_id', $inventory->id))
            ->with(['items' => fn ($q) => $q->where('inventory_id', $inventory->id)->with('unit:id,unit_number')])
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
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

            $items      = $repair->items()->with(['unit', 'inventory'])->get();
            $hasil      = $data['hasil'] ?? [];
            $costs      = $items->mapWithKeys(fn ($item) => [$item->id => $this->cleanCost($data['biaya_item'][$item->id] ?? null)]);
            $totalBiaya = $costs->filter()->sum();

            if ($totalBiaya > 0) {
                FinanceLock::assertOpen($data['tanggal_selesai']);
            }

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

                $item->update([
                    'hasil'          => $result,
                    'catatan'        => $this->groupNote($data, (int) $item->inventory_id, $result),
                    'biaya'          => $costs[$item->id],
                    'biaya_estimasi' => false,
                ]);
                ($result === 'Tersedia' ? $tersedia : $rusak)->push($item);
            }

            $repair->update([
                'status'          => InventoryRepair::STATUS_DONE,
                'tanggal_selesai' => $data['tanggal_selesai'],
                'diambil_oleh'    => $data['diambil_oleh'] ?? null,
                'catatan_hasil'   => $data['catatan_hasil'] ?? null,
                'biaya'           => $costs->filter(fn ($cost) => $cost !== null)->isEmpty() ? null : $totalBiaya,
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

    /** Satu catatan per barang dan hasil; semua unit di kelompok itu memakai catatan yang sama. */
    private function groupNote(array $data, int $inventoryId, string $result): ?string
    {
        $note = trim((string) ($data['catatan'][$inventoryId][$result] ?? ''));

        return $note === '' ? null : $note;
    }

    /** Biaya kosong berarti belum diketahui (null), bukan nol. */
    private function cleanCost(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
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
     * Biaya diambil dari biaya per unit; catatan lama yang dibagi rata ditandai servis_estimasi.
     *
     * price_source: pembelian | manual (harga manual hanya jadi cadangan bila belum ada Pembelian diterima).
     *
     * @return array<int, array{last_price: ?float, last_date: ?\Carbon\Carbon, last_code: ?string, last_purchase_id: ?int, price_source: ?string, servis_total: float, servis_count: int, servis_estimasi: bool, worst_unit: ?array{number: int, total: float}, units: array<int, array{number: int, total: float}>, warn_ratio: float}>
     */
    public function costComparison(): array
    {
        $shares = DB::table('inventory_repair_items as i')
            ->join('inventory_repairs as r', 'r.id', '=', 'i.repair_id')
            ->join('inventory_units as u', 'u.id', '=', 'i.inventory_unit_id')
            ->where('r.status', InventoryRepair::STATUS_DONE)
            ->where('i.biaya', '>', 0)
            ->selectRaw('i.inventory_id, i.inventory_unit_id, u.unit_number, r.id as repair_id, i.biaya as share, i.biaya_estimasi as estimasi')
            ->get();

        $empty = self::emptyCost();
        $map = [];

        foreach ($shares->groupBy('inventory_id') as $inventoryId => $rows) {
            $perUnit = $rows->groupBy('inventory_unit_id')
                ->map(fn ($g) => ['number' => (int) $g->first()->unit_number, 'total' => round((float) $g->sum('share'), 2)]);

            $map[(int) $inventoryId] = array_merge($empty, [
                'servis_total' => round((float) $rows->sum('share'), 2),
                'servis_count' => $rows->pluck('repair_id')->unique()->count(),
                'servis_estimasi' => $rows->contains(fn ($row) => (bool) $row->estimasi),
                'worst_unit'   => $perUnit->sortByDesc('total')->first(),
                'units'        => $perUnit->all(),
            ]);
        }

        $purchases = $this->receivedPrices()
            ->get(['purchase_items.inventory_id', 'purchase_items.unit_price', 'purchases.code', 'purchases.id as purchase_id', 'purchases.purchase_date'])
            ->unique('inventory_id')
            ->keyBy('inventory_id');

        $manuals = Inventory::where('harga_manual', '>', 0)
            ->get(['id', 'harga_manual', 'harga_manual_at'])
            ->keyBy('id');

        foreach (array_unique([...$purchases->keys()->all(), ...$manuals->keys()->all()]) as $inventoryId) {
            $price = $this->newerPrice(
                $this->purchasePrice($purchases->get($inventoryId)),
                $this->manualPrice($manuals->get($inventoryId))
            );

            if ($price !== null) {
                $map[(int) $inventoryId] = array_merge($map[(int) $inventoryId] ?? $empty, [
                    'last_price'       => $price['price'],
                    'last_date'        => $price['date'],
                    'last_code'        => $price['code'],
                    'last_purchase_id' => $price['purchase_id'],
                    'price_source'     => $price['source'],
                ]);
            }
        }

        // Ambang ganti khusus barang menggantikan nilai Pengaturan.
        $ambang = Inventory::whereNotNull('ambang_ganti_persen')->pluck('ambang_ganti_persen', 'id');

        foreach ($map as $inventoryId => $row) {
            $map[$inventoryId]['warn_ratio'] = self::warnRatioFor($ambang->has($inventoryId) ? (int) $ambang[$inventoryId] : null);
        }

        return $map;
    }

    /** Item Pembelian berstatus diterima, terbaru dulu; dasar harga beli terakhir. */
    private function receivedPrices(): \Illuminate\Database\Eloquent\Builder
    {
        return PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchases.status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_PARTIAL])
            ->whereNotNull('purchase_items.inventory_id')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id');
    }

    /**
     * Harga beli terakhir beberapa barang sekaligus, dikunci id barang: Pembelian diterima, atau harga manual bila belum ada.
     *
     * @param  iterable<Inventory>  $inventories
     * @return array<int, array{price: float, date: ?\Carbon\Carbon, source: string, code: ?string, purchase_id: ?int}>
     */
    public function latestPrices(iterable $inventories): array
    {
        $inventories = collect($inventories);
        $purchases   = $this->receivedPrices()
            ->whereIn('purchase_items.inventory_id', $inventories->pluck('id')->all())
            ->get(['purchase_items.inventory_id', 'purchase_items.unit_price', 'purchases.code', 'purchases.id as purchase_id', 'purchases.purchase_date'])
            ->unique('inventory_id')
            ->keyBy('inventory_id');

        $map = [];

        foreach ($inventories as $inventory) {
            $price = $this->newerPrice(
                $this->purchasePrice($purchases->get($inventory->id)),
                $this->manualPrice($inventory)
            );

            if ($price !== null) {
                $map[(int) $inventory->id] = $price;
            }
        }

        return $map;
    }

    /** Harga Pembelian diterima; tanggal = tanggal Pembelian (awal hari). */
    private function purchasePrice(?object $row): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'price'       => (float) $row->unit_price,
            'date'        => \Carbon\Carbon::parse($row->purchase_date)->startOfDay(),
            'source'      => 'pembelian',
            'code'        => $row->code,
            'purchase_id' => (int) $row->purchase_id,
        ];
    }

    /** Harga manual; tanggal = saat terakhir diisi atau diubah. */
    private function manualPrice(?Inventory $inventory): ?array
    {
        if ($inventory === null || (float) $inventory->harga_manual <= 0) {
            return null;
        }

        return [
            'price'       => (float) $inventory->harga_manual,
            'date'        => $inventory->harga_manual_at,
            'source'      => 'manual',
            'code'        => null,
            'purchase_id' => null,
        ];
    }

    /**
     * Harga yang berlaku: yang tanggalnya paling baru. Harga manual yang diubah setelah Pembelian menang,
     * dan Pembelian yang lebih baru menggantikan harga manual. Bila tanggal manual tidak ada, Pembelian dipakai.
     */
    private function newerPrice(?array $purchase, ?array $manual): ?array
    {
        if ($purchase === null || $manual === null) {
            return $purchase ?? $manual;
        }

        if ($manual['date'] === null) {
            return $purchase;
        }

        return $manual['date']->gte($purchase['date']) ? $manual : $purchase;
    }

    /**
     * Harga beli terakhir satu barang.
     *
     * @return array{price: float, date: ?\Carbon\Carbon, source: string, code: ?string, purchase_id: ?int}|null
     */
    public function latestPrice(Inventory $inventory): ?array
    {
        return $this->latestPrices([$inventory])[$inventory->id] ?? null;
    }

    /**
     * Daftar harga barang: harga beli terakhir berdampingan dengan biaya servisnya.
     *
     * @param array{search?: ?string, filter?: ?string} $filters filter: servis | ganti | kosong
     * @return array{items: LengthAwarePaginator, costs: array, events: array<int, int>, summary: array<string, int|float>}
     */
    public function getPriceList(array $filters = [], int $perPage = 20): array
    {
        $costs = $this->costComparison();
        $activeIds = Inventory::pluck('id')->all();
        $costs = array_intersect_key($costs, array_flip($activeIds));
        $events = array_intersect_key($this->serviceEvents(), array_flip($activeIds));

        $filterIds = match ($filters['filter'] ?? null) {
            'servis' => array_keys($events),
            'ganti'  => array_keys(array_filter($costs, fn ($c) => self::isReplaceWarn($c))),
            default  => null,
        };

        $withoutPrice = ($filters['filter'] ?? null) === 'kosong'
            ? array_values(array_diff($activeIds, array_keys(array_filter($costs, fn ($c) => $c['last_price'] !== null))))
            : null;

        $items = Inventory::withCount('units')
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(fn ($w) => $w->where('name', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%")
                    ->orWhere('serial_number', 'like', "%{$keyword}%"));
            })
            ->when($filterIds !== null, fn ($q) => $q->whereIn('id', $filterIds))
            ->when($withoutPrice !== null, fn ($q) => $q->whereIn('id', $withoutPrice))
            ->orderBy('name')
            ->paginate(PerPage::resolve($perPage))
            ->withQueryString();

        $summary = [
            'total_barang' => count($activeIds),
            'ada_harga'    => count(array_filter($costs, fn ($c) => $c['last_price'] !== null)),
            'layak_ganti'  => count(array_filter($costs, fn ($c) => self::isReplaceWarn($c))),
        ];

        return ['items' => $items, 'costs' => $costs, 'events' => $events, 'summary' => $summary];
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

    /** Biaya servis unit paling mahal sudah menyentuh ambang ganti barang itu. */
    public static function isReplaceWarn(array $cost): bool
    {
        $ratio = self::replaceRatio($cost);

        return $ratio !== null && $ratio >= ($cost['warn_ratio'] ?? self::replaceWarnRatio());
    }

    /**
     * Jumlah kejadian servis selesai per barang (unit x surat), terlepas dari ada tidaknya biaya.
     *
     * @return array<int, int>
     */
    public function serviceEvents(): array
    {
        return DB::table('inventory_repair_items as i')
            ->join('inventory_repairs as r', 'r.id', '=', 'i.repair_id')
            ->where('r.status', InventoryRepair::STATUS_DONE)
            ->groupBy('i.inventory_id')
            ->selectRaw('i.inventory_id, COUNT(*) as total')
            ->pluck('total', 'inventory_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Rata-rata biaya servis per unit yang pernah diservis, sebanding dengan harga beli per unit.
     */
    public static function averagePerUnit(array $cost): ?float
    {
        $count = count($cost['units'] ?? []);

        return $count > 0 ? round($cost['servis_total'] / $count, 2) : null;
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

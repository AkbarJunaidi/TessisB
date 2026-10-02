<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Master Lokasi + penempatan unit barang.
 *
 * Konsep:
 * - Lokasi utama  : tempat simpan semestinya sebuah unit.
 * - Lokasi sekarang: tempat unit terakhir disimpan (bisa berbeda, mis. berkumpul di kantor lain
 *                    setelah event). Unit yang sedang dipinjam lewat Surat Jalan tetap dianggap
 *                    "di lapangan" - dihitung dari surat_jalan_item_id, bukan dari kolom ini.
 * - Deteksi GPS   : posisi perangkat dicocokkan ke lokasi yang punya koordinat dalam radius
 *                   (default 100 m). Hasilnya hanya alat bantu: user selalu bisa memilih manual.
 */
class LocationService
{
    public const METODE_GPS      = 'gps';
    public const METODE_MANUAL   = 'manual';
    public const METODE_LUAR     = 'luar_jangkauan';
    public const METODE_AKURASI  = 'akurasi_rendah';

    private const METODE_LABEL = [
        self::METODE_GPS     => 'terdeteksi GPS',
        self::METODE_MANUAL  => 'dipilih manual',
        self::METODE_LUAR    => 'di luar jangkauan, dipilih manual',
        self::METODE_AKURASI => 'akurasi GPS rendah, dipilih manual',
    ];

    private const EARTH_RADIUS_M = 6371000;

    /**
     * Aturan validasi input lokasi dari browser (lokasi terpilih + koordinat perangkat).
     * Dipakai endpoint Scan dan Barang Pinjaman; lokasi_id wajib agar tiap aksi punya lokasi.
     */
    public const INPUT_RULES = [
        'lokasi_id' => ['required', 'integer'],
        'lat'       => ['nullable', 'numeric', 'between:-90,90'],
        'lng'       => ['nullable', 'numeric', 'between:-180,180'],
        'accuracy'  => ['nullable', 'numeric', 'min:0'],
    ];

    public static function metodeLabel(string $metode): string
    {
        return self::METODE_LABEL[$metode] ?? $metode;
    }

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryMutationService $mutationService
    ) {}

    // ------------------------------------------------------------------
    //  Fungsi murni (tanpa database) - mudah diuji
    // ------------------------------------------------------------------

    /**
     * Jarak dua titik koordinat dalam meter (rumus haversine).
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_M * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Membaca koordinat dari teks yang ditempel user. Format yang dikenali:
     *  - "-7.2575, 112.7521" (juga dipisah spasi atau titik koma)
     *  - tautan Google Maps yang memuat "@lat,lng", "!3dLAT!4dLNG", atau "?q=lat,lng"
     *
     * @return array{lat: float, lng: float}|null null bila kosong / tidak dikenali / di luar rentang
     */
    public static function parseCoordinates(?string $input): ?array
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Salinan dari beberapa sumber memakai tanda minus Unicode.
        $input = str_replace(["\u{2212}", "\u{2013}"], '-', $input);

        $num = '(-?\d{1,3}(?:\.\d+)?)';
        $patterns = [
            '/!3d' . $num . '!4d' . $num . '/',
            '/@' . $num . ',' . $num . '/',
            '/[?&]q=' . $num . ',' . $num . '/',
            '/^\s*' . $num . '\s*[,;\s]\s*' . $num . '\s*$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];

                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    return ['lat' => $lat, 'lng' => $lng];
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Cocokkan posisi perangkat ke lokasi terdekat yang masih dalam radiusnya.
     *
     * Hasil:
     *  - terdeteksi     : ada lokasi dalam radius dan akurasi GPS memadai (location, distance_m)
     *  - akurasi_rendah : ada lokasi dalam radius tetapi akurasi GPS lebih buruk dari radius,
     *                     sehingga tidak dipilih otomatis (location, distance_m, accuracy_m)
     *  - luar_jangkauan : tidak ada lokasi dalam radius
     *
     * @param iterable<Location> $locations lokasi kandidat (yang punya koordinat)
     */
    public function detectFrom(iterable $locations, float $lat, float $lng, ?float $accuracy = null): array
    {
        $nearest  = null;
        $nearestD = null;

        foreach ($locations as $location) {
            if (!$location->hasCoordinates()) {
                continue;
            }

            $distance = self::haversine($lat, $lng, (float) $location->latitude, (float) $location->longitude);

            if ($distance <= $location->radius_m && ($nearestD === null || $distance < $nearestD)) {
                $nearest  = $location;
                $nearestD = $distance;
            }
        }

        if ($nearest === null) {
            return ['status' => self::METODE_LUAR];
        }

        if ($accuracy !== null && $accuracy > $nearest->radius_m) {
            return [
                'status'     => self::METODE_AKURASI,
                'location'   => $nearest,
                'distance_m' => (int) round($nearestD),
                'accuracy_m' => (int) round($accuracy),
            ];
        }

        return [
            'status'     => 'terdeteksi',
            'location'   => $nearest,
            'distance_m' => (int) round($nearestD),
        ];
    }

    /**
     * Cara lokasi sebuah perpindahan ditentukan. Dihitung ULANG di server dari koordinat
     * yang dikirim - bukan mempercayai klaim dari browser.
     */
    public function resolveMetode(?Location $target, ?float $lat, ?float $lng, ?float $accuracy): string
    {
        if ($lat === null || $lng === null || $target === null || !$target->hasCoordinates()) {
            return self::METODE_MANUAL;
        }

        $distance = self::haversine($lat, $lng, (float) $target->latitude, (float) $target->longitude);

        if ($distance > $target->radius_m) {
            return self::METODE_LUAR;
        }

        if ($accuracy !== null && $accuracy > $target->radius_m) {
            return self::METODE_AKURASI;
        }

        return self::METODE_GPS;
    }

    /**
     * Bentuk LocationContext dari input browser (lihat INPUT_RULES). Metode dihitung ulang
     * di server dari koordinat, bukan dipercaya dari klaim klien.
     *
     * @param  array<string, mixed> $input
     * @throws Exception
     */
    public function contextFromInput(array $input): LocationContext
    {
        $location = $this->findStorageLocation((int) ($input['lokasi_id'] ?? 0));

        if (!$location) {
            throw new Exception('Lokasi tidak ditemukan atau tidak dapat dipakai untuk menyimpan unit.');
        }

        $lat      = isset($input['lat']) && $input['lat'] !== '' ? (float) $input['lat'] : null;
        $lng      = isset($input['lng']) && $input['lng'] !== '' ? (float) $input['lng'] : null;
        $accuracy = isset($input['accuracy']) && $input['accuracy'] !== '' ? (float) $input['accuracy'] : null;

        return new LocationContext(
            $location,
            $this->resolveMetode($location, $lat, $lng, $accuracy),
            $lat,
            $lng,
            $accuracy !== null ? (int) round($accuracy) : null
        );
    }

    /**
     * Susun rencana pindah: validasi tiap unit lalu kelompokkan menurut lokasi asal
     * (1 kelompok = 1 baris Mutasi Aset).
     *
     * @param  iterable<InventoryUnit> $units
     * @return array<int, array{asal_id: ?int, asal_name: string, unit_ids: int[], unit_numbers: int[], home_changed: bool}>
     *
     * @throws Exception
     */
    public function planMove(iterable $units, Location $target, bool $makeHome): array
    {
        $groups = [];

        foreach ($units as $unit) {
            if ($unit->isOnLoan()) {
                throw new Exception(
                    "Unit #{$unit->unit_number} sedang dipinjam lewat Surat Jalan dan tidak dapat dipindah lokasi. Tunggu sampai dikembalikan."
                );
            }

            if ($unit->status === 'Hilang') {
                throw new Exception("Unit #{$unit->unit_number} berstatus Hilang dan tidak dapat dipindah lokasi.");
            }

            $current       = $unit->lokasi_sekarang_id !== null ? (int) $unit->lokasi_sekarang_id : null;
            $needsMove     = $current !== (int) $target->id;
            $needsHomeSet  = $makeHome && (int) $unit->lokasi_utama_id !== (int) $target->id;

            if (!$needsMove && !$needsHomeSet) {
                continue; // sudah sesuai, lewati
            }

            $key = $current ?? 0;

            $groups[$key] ??= [
                'asal_id'      => $current,
                'asal_name'    => $unit->lokasiSekarang?->name ?? 'Tanpa lokasi',
                'unit_ids'     => [],
                'unit_numbers' => [],
                'home_changed' => false,
            ];

            $groups[$key]['unit_ids'][]     = (int) $unit->id;
            $groups[$key]['unit_numbers'][] = (int) $unit->unit_number;
            $groups[$key]['home_changed']   = $groups[$key]['home_changed'] || $needsHomeSet;
        }

        if ($groups === []) {
            throw new Exception('Unit yang dipilih sudah berada di lokasi tersebut.');
        }

        return array_values($groups);
    }

    /**
     * Teks Keterangan Mutasi Aset, mis. "Unit 1, 3: Kantor B -> Kantor A (terdeteksi GPS)".
     */
    public function buildKeterangan(array $group, Location $target, string $metode, bool $makeHome): string
    {
        $numbers = $group['unit_numbers'];
        sort($numbers);

        $text = 'Unit ' . implode(', ', $numbers) . ": {$group['asal_name']} -> {$target->name}"
            . ' (' . self::metodeLabel($metode) . ')';

        if ($makeHome && $group['home_changed']) {
            $text .= '; lokasi utama diubah';
        }

        // Kolom keterangan maksimal 255 karakter; Str::limit menambahkan "..." DI LUAR batas,
        // jadi batasnya dikurangi panjang penanda itu.
        return Str::limit($text, 252, '...');
    }

    // ------------------------------------------------------------------
    //  Operasi dengan database
    // ------------------------------------------------------------------

    /**
     * Daftar lokasi untuk halaman Lokasi, dengan filter pencarian dan jenis.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return Location::query()
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $keyword = trim($filters['search']);
                $q->where(function ($w) use ($keyword) {
                    $w->where('name', 'like', "%{$keyword}%")
                      ->orWhere('address', 'like', "%{$keyword}%");
                });
            })
            ->when(!empty($filters['jenis']) && isset(Location::JENIS[$filters['jenis']]), fn ($q) => $q->where('jenis', $filters['jenis']))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ringkasan unit per lokasi untuk halaman Lokasi.
     *
     * @return array{per_location: array<int, array{di_lokasi:int, tersedia:int, utama:int, perlu_kembali:int}>, di_lapangan: int}
     */
    public function getUnitStats(): array
    {
        $now = InventoryUnit::query()
            ->whereNotNull('lokasi_sekarang_id')
            ->whereNull('surat_jalan_item_id')
            ->selectRaw('lokasi_sekarang_id as location_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as tersedia', ['Tersedia'])
            ->groupBy('lokasi_sekarang_id')
            ->get()
            ->keyBy('location_id');

        $home = InventoryUnit::query()
            ->whereNotNull('lokasi_utama_id')
            ->selectRaw('lokasi_utama_id as location_id, COUNT(*) as total')
            ->groupBy('lokasi_utama_id')
            ->pluck('total', 'location_id');

        $away = InventoryUnit::query()
            ->whereNull('surat_jalan_item_id')
            ->whereNotNull('lokasi_utama_id')
            ->whereNotNull('lokasi_sekarang_id')
            ->whereColumn('lokasi_utama_id', '!=', 'lokasi_sekarang_id')
            ->selectRaw('lokasi_utama_id as location_id, COUNT(*) as total')
            ->groupBy('lokasi_utama_id')
            ->pluck('total', 'location_id');

        $perLocation = [];
        foreach (Location::pluck('id') as $id) {
            $perLocation[$id] = [
                'di_lokasi'     => (int) ($now[$id]->total ?? 0),
                'tersedia'      => (int) ($now[$id]->tersedia ?? 0),
                'utama'         => (int) ($home[$id] ?? 0),
                'perlu_kembali' => (int) ($away[$id] ?? 0),
            ];
        }

        return [
            'per_location' => $perLocation,
            'di_lapangan'  => InventoryUnit::whereNotNull('surat_jalan_item_id')->count(),
        ];
    }

    /**
     * Autocomplete: cari lokasi aktif menurut nama/alamat.
     */
    public function search(string $keyword, bool $storageOnly = false, int $limit = 8): Collection
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return collect();
        }

        return Location::active()
            ->when($storageOnly, fn ($q) => $q->storage())
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Lokasi kantor/gudang aktif (pilihan manual saat memindahkan unit).
     */
    public function getStorageLocations(): Collection
    {
        return Location::active()->storage()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'jenis']);
    }

    /**
     * Deteksi lokasi dari koordinat perangkat (hanya lokasi penyimpanan yang aktif).
     */
    public function detect(float $lat, float $lng, ?float $accuracy = null): array
    {
        return $this->detectFrom($this->detectionCandidates(), $lat, $lng, $accuracy);
    }

    /**
     * @throws Exception
     */
    public function create(array $data): Location
    {
        $this->assertDefaultRules($data);

        return $this->runInTransaction(function () use ($data) {
            $location = Location::create($data + ['created_by' => Auth::id()]);

            if ($location->is_default) {
                $this->makeDefault($location);
            }

            $this->activityLogService->log(Auth::id(), 'Inventory', 'Create Lokasi');

            return $location;
        });
    }

    /**
     * @throws Exception
     */
    public function update(Location $location, array $data): Location
    {
        $this->assertDefaultRules($data);

        $stopsStoring = (!$data['can_store_units'] && $location->can_store_units)
            || (!$data['is_active'] && $location->is_active);

        if ($stopsStoring) {
            $used = $this->unitsUsingCount($location);

            if ($used > 0) {
                throw new Exception(
                    "Lokasi \"{$location->name}\" masih dipakai oleh {$used} unit sehingga tidak bisa dinonaktifkan atau dicabut fungsi penyimpanannya. Pindahkan unitnya terlebih dahulu."
                );
            }
        }

        if ($location->is_default && !$data['is_default']) {
            throw new Exception(
                'Harus ada satu lokasi awal. Jadikan lokasi lain sebagai lokasi awal terlebih dahulu.'
            );
        }

        return $this->runInTransaction(function () use ($location, $data) {
            $location->update($data);

            if ($location->is_default) {
                $this->makeDefault($location);
            }

            $this->activityLogService->log(Auth::id(), 'Inventory', 'Update Lokasi');

            return $location;
        });
    }

    /**
     * @throws Exception
     */
    public function delete(Location $location): void
    {
        if ($location->is_default) {
            throw new Exception(
                'Lokasi awal tidak dapat dihapus. Jadikan lokasi lain sebagai lokasi awal terlebih dahulu.'
            );
        }

        $used = $this->unitsUsingCount($location);

        if ($used > 0) {
            throw new Exception(
                "Lokasi \"{$location->name}\" masih dipakai oleh {$used} unit. Pindahkan unitnya terlebih dahulu."
            );
        }

        $location->delete();

        $this->activityLogService->log(Auth::id(), 'Inventory', 'Delete Lokasi');
    }

    /**
     * Koreksi lokasi unit yang ternyata fisiknya ada di lokasi $ctx (mis. saat dipinjam lewat
     * Scan di lokasi itu). Dicatat sebagai Pindah Lokasi per lokasi asal.
     *
     * @param Collection<int, InventoryUnit> $units unit dengan relasi lokasiSekarang ter-load
     */
    public function correctUnitsTo(Inventory $inventory, Collection $units, LocationContext $ctx, string $alasan): void
    {
        $groups = $units
            ->filter(fn ($unit) => (int) $unit->lokasi_sekarang_id !== (int) $ctx->location->id)
            ->groupBy(fn ($unit) => $unit->lokasi_sekarang_id ?? 0);

        foreach ($groups as $asalId => $group) {
            InventoryUnit::whereIn('id', $group->pluck('id'))->update(['lokasi_sekarang_id' => $ctx->location->id]);

            $numbers = $group->pluck('unit_number')->sort()->values()->all();
            $asalName = $group->first()->lokasiSekarang?->name ?? 'Tanpa lokasi';

            $this->mutationService->recordLocationMove(
                $inventory->id,
                $group->count(),
                $asalId ?: null,
                $ctx->location->id,
                $ctx->metode,
                $ctx->lat,
                $ctx->lng,
                $ctx->accuracyM,
                Str::limit('Unit ' . implode(', ', $numbers) . ": {$asalName} -> {$ctx->location->name} ({$alasan})", 252, '...')
            );
        }
    }

    /**
     * Jumlah unit yang siap dipinjam (kondisi Tersedia, tidak sedang dipinjam) per lokasi.
     *
     * @return array<int, array{id: int|null, name: string, qty: int}>
     */
    public function availableByLocation(Inventory $inventory): array
    {
        return $inventory->units()
            ->where('status', 'Tersedia')
            ->whereNull('surat_jalan_item_id')
            ->with('lokasiSekarang:id,name')
            ->get()
            ->groupBy(fn ($unit) => $unit->lokasi_sekarang_id ?? 0)
            ->map(fn ($group, $id) => [
                'id'   => $id ?: null,
                'name' => $group->first()->lokasiSekarang?->name ?? 'Tanpa lokasi',
                'qty'  => $group->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * Pindahkan beberapa unit satu barang ke sebuah lokasi.
     *
     * @param  int[] $unitIds
     * @return int   jumlah unit yang benar-benar diubah
     *
     * @throws Exception
     */
    public function moveUnits(
        Inventory $inventory,
        array $unitIds,
        int $targetLocationId,
        bool $makeHome = false,
        ?float $lat = null,
        ?float $lng = null,
        ?float $accuracy = null
    ): int {
        $target = $this->findStorageLocation($targetLocationId);

        if (!$target) {
            throw new Exception('Lokasi tujuan tidak ditemukan atau tidak dapat dipakai untuk menyimpan unit.');
        }

        $metode = $this->resolveMetode($target, $lat, $lng, $accuracy);

        return $this->runInTransaction(function () use ($inventory, $unitIds, $target, $makeHome, $lat, $lng, $accuracy, $metode) {
            $units = $this->lockUnits($inventory->id, $unitIds);

            if ($units->count() !== count(array_unique($unitIds))) {
                throw new Exception('Sebagian unit tidak ditemukan pada barang ini.');
            }

            $moved = 0;

            foreach ($this->planMove($units, $target, $makeHome) as $group) {
                $this->applyUnitUpdate($group['unit_ids'], $target->id, $makeHome);

                $this->mutationService->recordLocationMove(
                    $inventory->id,
                    count($group['unit_ids']),
                    $group['asal_id'],
                    $target->id,
                    $metode,
                    $lat,
                    $lng,
                    $accuracy !== null ? (int) round($accuracy) : null,
                    $this->buildKeterangan($group, $target, $metode, $makeHome)
                );

                $moved += count($group['unit_ids']);
            }

            $this->activityLogService->log(Auth::id(), 'Inventory', 'Pindah Lokasi Unit');

            return $moved;
        });
    }

    // ------------------------------------------------------------------
    //  Internal
    // ------------------------------------------------------------------

    /**
     * @throws Exception
     */
    private function assertDefaultRules(array $data): void
    {
        if (!empty($data['is_default']) && (empty($data['can_store_units']) || empty($data['is_active']))) {
            throw new Exception('Lokasi awal harus aktif dan dapat menyimpan unit barang.');
        }
    }

    private function makeDefault(Location $location): void
    {
        $this->clearOtherDefaults($location->id);
    }

    // Pengambilan/penulisan data dipisah ke method kecil agar logika di atas mudah diuji.

    protected function runInTransaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    /**
     * @return iterable<Location>
     */
    protected function detectionCandidates(): iterable
    {
        return Location::active()->storage()->withCoordinates()->get();
    }

    protected function findStorageLocation(int $id): ?Location
    {
        return Location::active()->storage()->find($id);
    }

    /**
     * @param  int[] $unitIds
     * @return Collection<int, InventoryUnit>
     */
    protected function lockUnits(int $inventoryId, array $unitIds): Collection
    {
        return InventoryUnit::with('lokasiSekarang:id,name')
            ->where('inventory_id', $inventoryId)
            ->whereIn('id', $unitIds)
            ->lockForUpdate()
            ->orderBy('unit_number')
            ->get();
    }

    /**
     * @param int[] $unitIds
     */
    protected function applyUnitUpdate(array $unitIds, int $targetId, bool $makeHome): void
    {
        InventoryUnit::whereIn('id', $unitIds)->update(
            ['lokasi_sekarang_id' => $targetId] + ($makeHome ? ['lokasi_utama_id' => $targetId] : [])
        );
    }

    protected function clearOtherDefaults(int $exceptId): void
    {
        Location::where('id', '!=', $exceptId)->where('is_default', true)->update(['is_default' => false]);
    }

    protected function unitsUsingCount(Location $location): int
    {
        return InventoryUnit::where('lokasi_utama_id', $location->id)
            ->orWhere('lokasi_sekarang_id', $location->id)
            ->count();
    }
}

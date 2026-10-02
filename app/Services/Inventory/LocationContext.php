<?php

namespace App\Services\Inventory;

use App\Models\Location;

/**
 * Lokasi tempat sebuah aksi (pinjam/kembali/rusak/hilang) dilakukan: lokasi yang dipilih
 * atau terdeteksi, cara penentuannya (dihitung ulang di server), dan koordinat perangkat.
 */
final class LocationContext
{
    public function __construct(
        public readonly Location $location,
        public readonly string $metode,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?int $accuracyM = null,
    ) {}

    /**
     * Kolom lokasi untuk satu baris Mutasi Aset.
     *
     * @return array<string, int|float|string|null>
     */
    public function mutationColumns(?int $asalId, ?int $tujuanId): array
    {
        return [
            'lokasi_asal_id'   => $asalId,
            'lokasi_tujuan_id' => $tujuanId,
            'lokasi_metode'    => $this->metode,
            'lokasi_lat'       => $this->lat,
            'lokasi_lng'       => $this->lng,
            'lokasi_akurasi_m' => $this->accuracyM,
        ];
    }

    /**
     * Potongan teks untuk Keterangan Mutasi, mis. "Kantor A, terdeteksi GPS".
     */
    public function label(): string
    {
        return $this->location->name . ', ' . LocationService::metodeLabel($this->metode);
    }
}

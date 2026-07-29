<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventories';

    protected $fillable = [
        'name',
        'serial_number',
        'description',
        'status',
        'brand',
        'image',
        'qr_code', // Kolom bawaan tetap dipertahankan untuk menyimpan path berkas gambar QR
        'quantity_total',
    ];

// Relasi ke Model InventoryAttribute (Has Many)
    public function attributes(): HasMany
    {
        return $this->hasMany(InventoryAttribute::class);
    }

// Relasi ke seluruh baris Surat Jalan Item yang pernah memakai barang ini (Has Many)
    public function suratJalanItems(): HasMany
    {
        return $this->hasMany(SuratJalanItem::class);
    }

// Relasi ke seluruh Unit Fisik barang ini (Has Many) - 1 serial number/QR untuk banyak unit
    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class)->orderBy('unit_number');
    }

    /**
     * Scope: hitung qty terpakai (Surat Jalan) DAN jumlah unit berstatus "Tersedia"
     * untuk SELURUH baris inventory dalam satu-dua query (withSum/withCount),
     * dipakai saat listing agar tidak N+1. Relasi units() juga di-eager-load
     * (jumlah baris kecil per inventory) untuk keperluan hitung status mayoritas.
     */
    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query
            ->withSum(
                ['suratJalanItems as qty_used_raw' => fn ($q) => $q],
                DB::raw('(qty_dipakai - qty_dikembalikan)')
            )
            ->withCount(['units as tersedia_units_count' => fn ($q) => $q->where('status', 'Tersedia')])
            ->with('units');
    }

    /**
     * Total unit yang sedang dipakai (belum dikembalikan) di seluruh Surat Jalan aktif.
     * Memakai hasil withAvailability() jika sudah di-eager-load (0 query tambahan);
     * jika tidak, fallback ke query langsung (dipakai untuk halaman detail 1 record).
     */
    protected function qtyInUse(): Attribute
    {
        return Attribute::get(function () {
            if (array_key_exists('qty_used_raw', $this->attributes)) {
                return (int) $this->attributes['qty_used_raw'];
            }

            return (int) $this->suratJalanItems()
                ->selectRaw('COALESCE(SUM(qty_dipakai - qty_dikembalikan), 0) as total')
                ->value('total');
        });
    }

    /**
     * Jumlah unit berstatus "Tersedia" (kondisi fisik baik, tidak Rusak/Perbaikan/Hilang).
     * Memakai hasil withAvailability() jika sudah di-eager-load; jika tidak, fallback query.
     */
    protected function tersediaUnitsCount(): Attribute
    {
        return Attribute::get(function () {
            if (array_key_exists('tersedia_units_count', $this->attributes)) {
                return (int) $this->attributes['tersedia_units_count'];
            }

            return $this->units()->where('status', 'Tersedia')->count();
        });
    }

    /**
     * Jumlah unit yang benar-benar bisa dipinjam via Surat Jalan sekarang:
     * unit yang kondisinya "Tersedia" DIKURANGI yang sedang dipakai di Surat Jalan aktif.
     */
    protected function qtyAvailable(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->tersedia_units_count - $this->qty_in_use));
    }

    /**
     * Status yang ditampilkan pada badge: selama masih ada unit yang bisa dipinjam,
     * tampilkan "Tersedia". Jika tidak ada sama sekali, tampilkan status yang paling
     * banyak dipegang oleh unit-unit fisiknya (status mayoritas per-unit yang sesungguhnya).
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(function () {
            if ($this->qty_available > 0) {
                return 'Tersedia';
            }

            // Hitung status mayoritas dari unit yang sudah di-eager-load (relationLoaded),
            // fallback ke kolom status lama jika belum ada unit sama sekali (data transisi).
            if ($this->relationLoaded('units') && $this->units->isNotEmpty()) {
                return $this->units->countBy('status')->sortDesc()->keys()->first();
            }

            if ($this->exists) {
                $majority = $this->units()->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')->orderByDesc('total')->value('status');

                if ($majority) {
                    return $majority;
                }
            }

            return $this->status;
        });
    }

// Akses ke URL QR Code yang dapat diakses publik.
    protected function qrCodeUrl(): Attribute
    {
        return Attribute::get(function () {
            // Memeriksa jika field qr_code di database terisi dan file fisiknya ada di storage
            if ($this->qr_code && Storage::disk('public')->exists($this->qr_code)) {
                return asset('storage/' . $this->qr_code);
            }

            // Fallback dinamis prediktif berbasis serial_number jika field database kosong
            $predictedFilename = 'qrcodes/' . $this->serial_number . '.svg';
            if ($this->serial_number && Storage::disk('public')->exists($predictedFilename)) {
                return asset('storage/' . $predictedFilename);
            }

            return null;
        });
    }
}

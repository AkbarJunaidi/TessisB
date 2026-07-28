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

    /**
     * Scope: hitung qty terpakai untuk SELURUH baris inventory dalam satu query
     * (withSum), dipakai saat listing (index, dropdown Surat Jalan) agar tidak N+1.
     * Tanpa scope ini, accessor qty_in_use akan fallback query per-baris (aman
     * dipakai untuk 1 record saja, misalnya halaman Detail Inventory).
     */
    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query->withSum(
            ['suratJalanItems as qty_used_raw' => fn ($q) => $q],
            DB::raw('(qty_dipakai - qty_dikembalikan)')
        );
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
     * Jumlah unit yang masih tersedia = total unit - yang sedang dipakai.
     */
    protected function qtyAvailable(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->quantity_total - $this->qty_in_use));
    }

    /**
     * Status yang ditampilkan pada badge: selama masih ada unit tersedia,
     * selalu tampilkan "Tersedia" walau sebagian unit sedang dipakai di Surat Jalan.
     * Jika tidak ada unit tersedia sama sekali, tampilkan status manual yang tersimpan.
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(fn () => $this->qty_available > 0 ? 'Tersedia' : $this->status);
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

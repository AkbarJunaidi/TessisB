<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    ];

    /**
     * Peta status barang ke warna Bootstrap.
     * Sumber tunggal warna badge status supaya konsisten
     * di semua halaman (List, Detail, PDF).
     */
    public const STATUS_COLORS = [
        'Tersedia'  => 'success',
        'Dipinjam'  => 'primary',
        'Perbaikan' => 'warning',
        'Rusak'     => 'danger',
        'Hilang'    => 'dark',
    ];

    /**
     * Warna Bootstrap untuk status barang ini
     * (dipakai untuk class bg-*, text-*, border-*).
     */
    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'success';
    }

// Relasi ke Model InventoryAttribute (Has Many)
    public function attributes(): HasMany
    {
        return $this->hasMany(InventoryAttribute::class);
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

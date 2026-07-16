<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'inventories';

    /**
     * Properti kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'serial_number',
        'image',
        'qr_code', // Kolom bawaan tetap dipertahankan untuk menyimpan path berkas gambar QR
    ];

    /**
     * TAHAP 4 IMPLEMENTASI: Eloquent Accessor untuk URL Publik QR Code.
     * * Method ini secara dinamis menghasilkan properti virtual '$inventory->qr_code_url'.
     * Digunakan oleh Blade View untuk merender tag <img> tanpa hardcoding direktori.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
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

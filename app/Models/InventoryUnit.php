<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryUnit extends Model
{
    use HasFactory;

    protected $table = 'inventory_units';

    protected $fillable = [
        'inventory_id',
        'unit_number',
        'status',
        'surat_jalan_item_id',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Baris Surat Jalan Item yang sedang meminjam unit ini (null kalau
     * unit sedang tidak dipinjam siapa pun / ada di gudang).
     */
    public function suratJalanItem(): BelongsTo
    {
        return $this->belongsTo(SuratJalanItem::class);
    }

    /**
     * Status yang benar-benar ditampilkan ke user: kalau unit sedang
     * dipinjam (terhubung ke Surat Jalan Item yang belum dikembalikan),
     * tampilkan "Dipinjam" - mengalahkan status kondisi manual (Tersedia/dst),
     * karena posisi fisiknya memang sedang keluar.
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(fn () => $this->surat_jalan_item_id ? 'Dipinjam' : $this->status);
    }
}

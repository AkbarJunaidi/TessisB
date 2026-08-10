<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalanItem extends Model
{
    use HasFactory;

    protected $table = 'surat_jalan_items';

    protected $fillable = [
        'surat_jalan_id',
        'inventory_id',
        'kategori_item',
        'qty_dipakai',
        'qty_dikembalikan',
    ];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Sisa qty yang masih dipakai (belum dikembalikan) untuk baris ini.
     */
    protected function qtyBelumKembali(): Attribute
    {
        return Attribute::get(fn () => $this->qty_dipakai - $this->qty_dikembalikan);
    }
}

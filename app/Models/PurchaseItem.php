<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    public const STOCK_NONE     = 'none';
    public const STOCK_EXISTING = 'existing';
    public const STOCK_NEW      = 'new';

    public const STOCK_MODES = [
        self::STOCK_NONE     => 'Bukan stok (habis pakai / jasa)',
        self::STOCK_EXISTING => 'Tambah stok barang yang ada',
        self::STOCK_NEW      => 'Daftarkan sebagai barang baru',
    ];

    protected $fillable = [
        'purchase_id',
        'name',
        'qty',
        'unit_price',
        'subtotal',
        'stock_mode',
        'inventory_id',
        'brand',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}

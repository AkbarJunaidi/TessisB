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
        'received_qty',
        'returned_qty',
        'unit_price',
        'actual_unit_price',
        'subtotal',
        'stock_mode',
        'inventory_id',
        'brand',
    ];

    protected $casts = [
        'unit_price'        => 'decimal:2',
        'actual_unit_price' => 'decimal:2',
        'subtotal'          => 'decimal:2',
    ];

    public function getRemainingQtyAttribute(): int
    {
        return max(0, $this->qty - $this->received_qty);
    }

    public function getReturnableQtyAttribute(): int
    {
        return max(0, $this->received_qty - $this->returned_qty);
    }

    /** Harga yang berlaku: aktual bila tercatat saat diterima, selain itu harga di PO. */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->actual_unit_price ?? $this->unit_price);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}

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
        'qty_received',
        'qty_returned',
        'unit_price',
        'po_unit_price',
        'subtotal',
        'stock_mode',
        'inventory_id',
        'brand',
    ];

    protected $casts = [
        'unit_price'    => 'decimal:2',
        'po_unit_price' => 'decimal:2',
        'subtotal'      => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /** Jumlah yang belum datang. */
    public function remaining(): int
    {
        return max($this->qty - $this->qty_received, 0);
    }

    /** Jumlah yang sudah datang dan belum diretur. */
    public function returnable(): int
    {
        return max($this->qty_received - $this->qty_returned, 0);
    }

    /** Nilai yang masih berlaku (jumlah dikurangi retur, dikali harga sekarang). */
    public function netValue(): float
    {
        return round(max($this->qty - $this->qty_returned, 0) * (float) $this->unit_price, 2);
    }
}

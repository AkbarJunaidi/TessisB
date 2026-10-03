<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 1 catatan = 1 kali pengiriman barang ke tempat servis (bisa berisi beberapa unit).
 * Unit yang sedang diservis berstatus "Perbaikan" dan menunjuk ke item catatan ini
 * lewat inventory_units.repair_item_id.
 */
class InventoryRepair extends Model
{
    public const STATUS_ACTIVE    = 'Diservis';
    public const STATUS_DONE      = 'Selesai';
    public const STATUS_CANCELLED = 'Dibatalkan';

    protected $table = 'inventory_repairs';

    protected $fillable = [
        'code',
        'vendor_id',
        'tempat_nama',
        'tempat_alamat',
        'status',
        'tanggal_masuk',
        'estimasi_selesai',
        'tanggal_selesai',
        'diantar_oleh',
        'diambil_oleh',
        'keluhan',
        'catatan_hasil',
        'biaya',
        'created_by',
        'completed_by',
    ];

    protected $casts = [
        'tanggal_masuk'    => 'date',
        'estimasi_selesai' => 'date',
        'tanggal_selesai'  => 'date',
        'biaya'            => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryRepairItem::class, 'repair_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Masih di tempat servis padahal estimasi selesainya sudah lewat.
     */
    public function isOverdue(): bool
    {
        return $this->isActive()
            && $this->estimasi_selesai !== null
            && $this->estimasi_selesai->lt(today());
    }
}

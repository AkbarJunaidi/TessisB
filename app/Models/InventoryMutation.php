<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris = 1 kejadian mutasi (lihat migration untuk daftar event_type).
 * Append-only - tidak ada method update/delete yang disengaja di Service-nya.
 */
class InventoryMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'event_type',
        'qty',
        'status_before',
        'status_after',
        'surat_jalan_id',
        'actor_id',
        'keterangan',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

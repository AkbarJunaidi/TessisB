<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'attribute_name',
        'attribute_value',
    ];

    /**
     * Relasi ke Model Inventory (Belongs To)
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}

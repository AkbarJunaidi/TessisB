<?php

namespace App\Models;

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
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}

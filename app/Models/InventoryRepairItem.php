<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRepairItem extends Model
{
    protected $table = 'inventory_repair_items';

    protected $fillable = [
        'repair_id',
        'inventory_id',
        'inventory_unit_id',
        'status_sebelum',
        'hasil',
    ];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(InventoryRepair::class, 'repair_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'inventory_unit_id');
    }
}

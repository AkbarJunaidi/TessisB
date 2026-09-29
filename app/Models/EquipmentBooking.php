<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentBooking extends Model
{
    public const STATUS_BOOKED    = 'Dipesan';
    public const STATUS_FULFILLED = 'Terpenuhi';
    public const STATUS_CANCELLED = 'Dibatalkan';

    public const STATUS_BADGES = [
        self::STATUS_BOOKED    => 'bg-warning text-dark',
        self::STATUS_FULFILLED => 'bg-success',
        self::STATUS_CANCELLED => 'bg-secondary',
    ];

    protected $fillable = [
        'project_id',
        'inventory_id',
        'qty',
        'status',
        'created_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }
}

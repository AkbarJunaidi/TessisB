<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['purchase_id', 'action', 'to_status', 'note', 'user_id'];

    protected $casts = ['created_at' => 'datetime'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

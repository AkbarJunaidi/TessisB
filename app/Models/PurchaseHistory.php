<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['purchase_id', 'user_id', 'event', 'label', 'note'];

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationState extends Model
{
    protected $fillable = [
        'user_id',
        'notification_key',
        'pinned_at',
        'dismissed_at',
    ];

    protected $casts = [
        'pinned_at'    => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

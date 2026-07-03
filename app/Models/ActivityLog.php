<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * Atribut yang dapat diisi menggunakan Mass Assignment.
     */
    protected $fillable = [
        'user_id',
        'module',
        'action',
    ];

    /**
     * Relasi ke user yang melakukan aktivitas.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

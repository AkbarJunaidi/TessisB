<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTypeSetting extends Model
{
    protected $fillable = [
        'type',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'sort_order' => 'integer',
    ];
}

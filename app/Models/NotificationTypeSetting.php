<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTypeSetting extends Model
{
    protected $fillable = [
        'type',
        'enabled',
        'sort_order',
        'days_before',
        'days_after',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'sort_order' => 'integer',
        'days_before' => 'integer',
        'days_after'  => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    /**
     * Nama tabel yang dikelola di dalam database MySQL.
     */
    protected $table = 'contacts';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'address',
        'created_by',
    ];

    /**
     * Relasi ke User pembuat/pencatat kontak ini (Belongs To).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

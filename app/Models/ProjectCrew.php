<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris Crew/Tim pada sebuah Project. Nama diinput bebas sebagai teks -
 * TIDAK terhubung ke tabel users, jadi crew tidak perlu punya akun untuk
 * bisa didaftarkan.
 */
class ProjectCrew extends Model
{
    use HasFactory;

    protected $table = 'project_crews';

    protected $fillable = [
        'project_id',
        'name',
        'role_label',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

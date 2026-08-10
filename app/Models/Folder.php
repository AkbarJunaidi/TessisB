<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel yang dikelola di dalam database MySQL.
     */
    protected $table = 'folders';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'name',
        'parent_id',
        'project_id',
        'created_by',
    ];

    /**
     * Relasi ke Project pemilik folder ini (Belongs To), jika folder ini
     * adalah folder khusus Document Center suatu project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relasi ke User pembuat folder (Belongs To).
     * Sinkronisasi mutlak untuk mengatasi RelationNotFoundException [user] di Controller.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi Alias ke User pembuat folder (Belongs To).
     * Dipertahankan agar tidak merusak kode lain jika sudah telanjur digunakan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Folder Induk (Self-Referencing Belongs To).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Relasi ke Anak Subfolder di dalamnya (Self-Referencing Has Many).
     */
    public function subfolders(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Relasi ke semua File yang ada di dalam folder ini (Has Many).
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'folder_id');
    }
}
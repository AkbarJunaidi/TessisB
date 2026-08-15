<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel yang dikelola di dalam MySQL database.
     */
    protected $table = 'files';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'folder_id',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'deleted_by',
    ];

    /**
     * Relasi ke Folder tempat file bernaung (Belongs To).
     * Dapat mengembalikan NULL jika file berada di root 'My Files' pribadi.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * Relasi ke User pemilik / pengunggah file (Belongs To).
     * Sinkronisasi mutlak untuk mengatasi RelationNotFoundException di View & Controller.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi Alias ke User pemilik file (Belongs To).
     * Dipertahankan sebagai back-up opsional business logic layer.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Aksesor opsional untuk mengubah ukuran byte menjadi format yang mudah dibaca (Human Readable).
     * Sangat berguna saat presentasi demo UI di hadapan penguji!
     */
    public function getReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Relasi BelongsTo: User yang melakukan penghapusan (untuk fitur Trash).
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
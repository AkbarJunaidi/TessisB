<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'created_by',
        'board_lists',
    ];

    protected $casts = [
        'board_lists' => 'array',
    ];

    /**
     * List/kolom board default, dipakai selama project belum
     * punya board_lists sendiri (kompatibel dengan data lama).
     */
    public const DEFAULT_BOARD_LISTS = [
        ['label' => 'Todo',        'color' => 'secondary'],
        ['label' => 'In Progress', 'color' => 'primary'],
        ['label' => 'Review',      'color' => 'warning'],
        ['label' => 'Done',        'color' => 'success'],
    ];

    /**
     * Palet warna yang dipakai berurutan untuk list baru yang ditambahkan user.
     */
    public const LIST_COLOR_PALETTE = [
        'secondary', 'primary', 'warning', 'success', 'info', 'danger', 'dark',
    ];

    /**
     * Daftar list/kolom board project ini.
     * Jika project belum punya board_lists sendiri, pakai default.
     */
    public function getBoardLists(): array
    {
        return $this->board_lists ?: self::DEFAULT_BOARD_LISTS;
    }

    /**
     * Relasi One-to-Many: Sebuah Project memiliki banyak Tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Relasi BelongsTo: Proyek ini diinisiasi oleh seorang User.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

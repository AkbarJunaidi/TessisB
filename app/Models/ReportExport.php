<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'status',
        'file_path',
        'error_message',
        'total_items',
        'processed_items',
        'requested_by',
        'downloaded_at',
    ];

    /**
     * Casting atribut.
     */
    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    /**
     * User yang meminta laporan ini dibuat.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope: laporan yang sudah selesai tapi belum pernah diunduh -
     * dipakai untuk notifikasi navbar "Laporan Siap Diunduh".
     */
    public function scopeReadyAndUndownloaded(Builder $query): Builder
    {
        return $query->where('status', 'completed')
            ->whereNull('downloaded_at');
    }

    /**
     * Apakah file laporan ini sudah siap diunduh.
     */
    public function isReady(): bool
    {
        return $this->status === 'completed' && $this->file_path !== null;
    }
}

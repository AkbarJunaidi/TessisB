<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'type',
        'requestable_type',
        'requestable_id',
        'payload',
        'status',
        'requested_by',
        'decided_by',
        'decided_at',
        'reason',
        'decision_note',
    ];

    protected $casts = [
        'payload'    => 'array',
        'decided_at' => 'datetime',
    ];

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Judul & detail ringkas untuk kartu Approval. Tambahkan case baru di
     * sini setiap ada jenis approval baru (lihat ApprovalService::HANDLERS
     * untuk sisi eksekusinya).
     */
    protected function displayTitle(): Attribute
    {
        return Attribute::get(fn () => match ($this->type) {
            'kwitansi_void' => "Pembatalan Kwitansi {$this->payload['nomor']}",
            'purchase_approve' => "Pembelian {$this->payload['code']}",
            default => class_basename($this->requestable_type) . " #{$this->requestable_id}",
        });
    }

    protected function displayDetail(): Attribute
    {
        return Attribute::get(fn () => match ($this->type) {
            'kwitansi_void' => ($this->payload['project_name'] ?? '-') . ' - ' . Money::formatRupiah($this->payload['jumlah'] ?? 0),
            'purchase_approve' => ($this->payload['vendor'] ?? '-') . ' - ' . Money::formatRupiah($this->payload['total'] ?? 0),
            default => '-',
        });
    }
}

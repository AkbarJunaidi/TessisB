<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_SUBMITTED = 'Diajukan';
    public const STATUS_APPROVED  = 'Disetujui';
    public const STATUS_REJECTED  = 'Ditolak';
    public const STATUS_RECEIVED  = 'Diterima';
    public const STATUS_CANCELLED = 'Dibatalkan';

    public const PAYMENT_UNPAID = 'Belum Dibayar';
    public const PAYMENT_PAID   = 'Dibayar';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'bg-secondary',
        self::STATUS_SUBMITTED => 'bg-warning text-dark',
        self::STATUS_APPROVED  => 'bg-info text-dark',
        self::STATUS_REJECTED  => 'bg-danger',
        self::STATUS_RECEIVED  => 'bg-success',
        self::STATUS_CANCELLED => 'bg-dark',
    ];

    protected $fillable = [
        'code',
        'vendor_id',
        'vendor_name',
        'project_id',
        'purchase_date',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'paid_at',
        'notes',
        'attachment',
        'created_by',
        'received_by',
        'paid_by',
        'submitted_at',
        'approved_at',
        'received_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'paid_at'       => 'date',
        'total'         => 'decimal:2',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'received_at'   => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'requestable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canReceive(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canPay(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_RECEIVED], true)
            && $this->payment_status === self::PAYMENT_UNPAID;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_APPROVED], true)
            && $this->payment_status === self::PAYMENT_UNPAID;
    }

    public function canDelete(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}

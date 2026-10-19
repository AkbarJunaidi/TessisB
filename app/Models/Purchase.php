<?php

namespace App\Models;

use App\Support\Money;
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
    public const STATUS_ORDERED   = 'Dipesan';
    public const STATUS_PARTIAL   = 'Diterima Sebagian';
    public const STATUS_RECEIVED  = 'Diterima';
    public const STATUS_CANCELLED = 'Dibatalkan';

    public const PAYMENT_UNPAID = 'Belum Dibayar';
    public const PAYMENT_PAID   = 'Dibayar';

    public const SHOP_VENDOR = 'vendor';
    public const SHOP_ONLINE = 'online';

    public const SHOP_CHANNELS = [
        self::SHOP_VENDOR => 'Vendor langsung',
        self::SHOP_ONLINE => 'Toko online',
    ];

    public const TIMING_UPFRONT    = 'upfront';
    public const TIMING_ON_RECEIPT = 'on_receipt';
    public const TIMING_TERM       = 'term';

    public const PAYMENT_TIMINGS = [
        self::TIMING_UPFRONT    => 'Bayar dulu',
        self::TIMING_ON_RECEIPT => 'Bayar saat terima',
        self::TIMING_TERM       => 'Tempo',
    ];

    public const FUND_REQUESTED  = 'requested';
    public const FUND_AT_FINANCE = 'at_finance';

    public const FUND_LABELS = [
        self::FUND_REQUESTED  => 'Dana diminta',
        self::FUND_AT_FINANCE => 'Dana di finance',
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ORDERED,
        self::STATUS_PARTIAL,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    /** Pembelian yang sudah disetujui dan masih berjalan atau selesai (dasar Hutang Vendor dan total belanja). */
    public const VALID_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_ORDERED,
        self::STATUS_PARTIAL,
        self::STATUS_RECEIVED,
    ];

    /** Jumlah hari sebelum jatuh tempo sampai pembelian ditandai "segera jatuh tempo". */
    public const DUE_SOON_DAYS = 3;

    public const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'bg-secondary',
        self::STATUS_SUBMITTED => 'bg-warning text-dark',
        self::STATUS_APPROVED  => 'bg-info text-dark',
        self::STATUS_REJECTED  => 'bg-danger',
        self::STATUS_ORDERED   => 'bg-primary',
        self::STATUS_PARTIAL   => 'bg-success bg-opacity-75',
        self::STATUS_RECEIVED  => 'bg-success',
        self::STATUS_CANCELLED => 'bg-dark',
    ];

    protected $fillable = [
        'code',
        'vendor_id',
        'vendor_name',
        'project_id',
        'shopping_channel',
        'payment_timing',
        'due_date',
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
        'submit_count',
        'approved_at',
        'received_at',
        'ordered_at',
        'ordered_by',
        'expected_arrival',
        'order_notes',
        'shop_name',
        'order_number',
        'order_url',
        'courier',
        'tracking_number',
        'fund_status',
        'fund_requested_at',
        'fund_amount',
        'fund_transferred_on',
        'fund_transferred_by',
        'fund_note',
    ];

    protected $casts = [
        'purchase_date'       => 'date',
        'paid_at'             => 'date',
        'due_date'            => 'date',
        'expected_arrival'    => 'date',
        'fund_transferred_on' => 'date',
        'total'               => 'decimal:2',
        'fund_amount'         => 'decimal:2',
        'submitted_at'        => 'datetime',
        'approved_at'         => 'datetime',
        'received_at'         => 'datetime',
        'ordered_at'          => 'datetime',
        'fund_requested_at'   => 'datetime',
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

    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function fundTransferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fund_transferred_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseStatusLog::class)->latest('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseAttachment::class)->latest('id');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'requestable');
    }

    public function isOnline(): bool
    {
        return $this->shopping_channel === self::SHOP_ONLINE;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    /** Menandai sudah dipesan (pertama kali). */
    public function canOrder(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Info pesanan (resi, estimasi tiba) masih boleh diperbarui. */
    public function canEditOrder(): bool
    {
        return $this->status === self::STATUS_ORDERED;
    }

    /** Dipesan dulu itu opsional: barang boleh langsung diterima dari status Disetujui. Bisa bertahap. */
    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ORDERED, self::STATUS_PARTIAL], true);
    }

    /** Ada barang yang sudah datang dan belum diretur ke vendor. */
    public function canReturn(): bool
    {
        if (!in_array($this->status, [self::STATUS_PARTIAL, self::STATUS_RECEIVED], true)) {
            return false;
        }

        return $this->items->contains(fn (PurchaseItem $item) => $item->returnable() > 0);
    }

    /** PO dicetak setelah disetujui, untuk dikirim ke vendor atau arsip. */
    public function canPrintPo(): bool
    {
        return in_array($this->status, self::VALID_STATUSES, true);
    }

    /**
     * Status jatuh tempo pembayaran: 'overdue' (lewat), 'soon' (dalam DUE_SOON_DAYS hari), atau null.
     */
    public function dueState(): ?string
    {
        if (!$this->due_date || !$this->canPay()) {
            return null;
        }

        $today = now()->startOfDay();

        if ($this->due_date->lt($today)) {
            return 'overdue';
        }

        return $this->due_date->lte($today->copy()->addDays(self::DUE_SOON_DAYS)) ? 'soon' : null;
    }

    /** Selisih hari ke jatuh tempo (negatif bila sudah lewat). */
    public function daysToDue(): ?int
    {
        return $this->due_date ? (int) now()->startOfDay()->diffInDays($this->due_date, false) : null;
    }

    public function canPay(): bool
    {
        return in_array($this->status, self::VALID_STATUSES, true)
            && $this->payment_status === self::PAYMENT_UNPAID;
    }

    /** Bos mencatat transfer dana ke finance; sekali saja, dan hanya sebelum dibayar. */
    public function canRecordFund(): bool
    {
        return $this->canPay() && $this->fund_status !== self::FUND_AT_FINANCE;
    }

    /** Setelah Diterima, jalannya lewat Retur. Pembelian yang sudah dibayar boleh dibatalkan dengan mencatat uang kembali. */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_APPROVED, self::STATUS_ORDERED], true);
    }

    public function canDelete(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** Pesan WhatsApp yang sudah terisi untuk menghubungi vendor soal pembelian ini. */
    public function whatsappMessage(): string
    {
        $lines = ["Halo {$this->vendor_name}, kami ingin memesan barang berikut (PO {$this->code}):"];

        foreach ($this->items as $item) {
            $lines[] = "- {$item->name} x{$item->qty}";
        }

        $lines[] = 'Total: ' . Money::formatRupiah($this->total);
        $lines[] = 'Mohon konfirmasi ketersediaan, total harga, dan estimasi pengiriman. Terima kasih.';

        return implode("\n", $lines);
    }
}

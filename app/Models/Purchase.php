<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Support\Money;

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

    /** Pembelian yang sudah disetujui dan masih berlaku (dihitung sebagai belanja/hutang). */
    public const COMMITTED = [self::STATUS_APPROVED, self::STATUS_ORDERED, self::STATUS_PARTIAL, self::STATUS_RECEIVED];

    /** Pengingat jatuh tempo muncul mulai sekian hari sebelum tanggalnya. */
    public const DUE_SOON_DAYS = 7;

    public const SHOP_VENDOR = 'vendor';
    public const SHOP_ONLINE = 'online';

    public const SHOP_TYPES = [
        self::SHOP_VENDOR => 'Vendor langsung',
        self::SHOP_ONLINE => 'Toko online',
    ];

    public const TIMING_PAY_FIRST   = 'pay_first';
    public const TIMING_ON_RECEIPT  = 'pay_on_receipt';
    public const TIMING_CREDIT      = 'credit';

    public const PAYMENT_TIMINGS = [
        self::TIMING_PAY_FIRST  => 'Bayar dulu',
        self::TIMING_ON_RECEIPT => 'Bayar saat terima',
        self::TIMING_CREDIT     => 'Tempo',
    ];

    public const FUND_REQUESTED  = 'requested';
    public const FUND_AT_FINANCE = 'at_finance';

    public const PAYMENT_METHODS = ['Transfer', 'Tunai', 'Lainnya'];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'badge-soft-secondary',
        self::STATUS_SUBMITTED => 'badge-soft-warning',
        self::STATUS_APPROVED  => 'badge-soft-secondary',
        self::STATUS_REJECTED  => 'badge-soft-danger',
        self::STATUS_ORDERED   => 'badge-soft-primary',
        self::STATUS_PARTIAL   => 'badge-soft-warning',
        self::STATUS_RECEIVED  => 'badge-soft-success',
        self::STATUS_CANCELLED => 'badge-soft-secondary',
    ];

    protected $fillable = [
        'code',
        'vendor_id',
        'vendor_name',
        'project_id',
        'shopping_type',
        'payment_timing',
        'due_date',
        'purchase_date',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'paid_amount',
        'paid_at',
        'notes',
        'attachment',
        'created_by',
        'received_by',
        'paid_by',
        'submitted_at',
        'resubmit_count',
        'approved_at',
        'ordered_at',
        'ordered_by',
        'expected_at',
        'store_name',
        'order_number',
        'order_url',
        'courier',
        'tracking_number',
        'fund_status',
        'fund_amount',
        'fund_at',
        'fund_by',
        'fund_note',
        'received_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'paid_at'       => 'date',
        'due_date'      => 'date',
        'expected_at'   => 'date',
        'fund_at'       => 'date',
        'total'         => 'decimal:2',
        'fund_amount'   => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'ordered_at'    => 'datetime',
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

    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function funder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fund_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PurchaseHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseAttachment::class)->orderBy('created_at')->orderBy('id');
    }

    public function isOnline(): bool
    {
        return $this->shopping_type === self::SHOP_ONLINE;
    }

    public function isPayFirst(): bool
    {
        return $this->payment_timing === self::TIMING_PAY_FIRST;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canOrder(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Info pesanan (estimasi, ekspedisi, resi) masih boleh diperbarui selama berstatus Dipesan. */
    public function canEditOrder(): bool
    {
        return $this->status === self::STATUS_ORDERED;
    }

    /** Pembelian lama (tanpa waktu bayar) boleh langsung diterima dari Disetujui. */
    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_ORDERED, self::STATUS_PARTIAL], true)
            || ($this->status === self::STATUS_APPROVED && $this->payment_timing === null);
    }

    /** Retur hanya untuk barang yang sudah masuk dan belum seluruhnya dikembalikan. */
    public function canReturn(): bool
    {
        return in_array($this->status, [self::STATUS_PARTIAL, self::STATUS_RECEIVED], true)
            && $this->items->contains(fn (PurchaseItem $item) => $item->returnable_qty > 0);
    }

    public function canFund(): bool
    {
        return $this->isPayFirst()
            && $this->fund_status === self::FUND_REQUESTED
            && $this->payment_status === self::PAYMENT_UNPAID
            && in_array($this->status, self::COMMITTED, true);
    }

    /** Aturan waktu bayar saja; siapa yang boleh membayar ada di canPayBy(). */
    public function canPay(): bool
    {
        if ($this->payment_status !== self::PAYMENT_UNPAID || !in_array($this->status, self::COMMITTED, true)) {
            return false;
        }

        return match ($this->payment_timing) {
            self::TIMING_ON_RECEIPT => $this->status === self::STATUS_RECEIVED,
            self::TIMING_CREDIT     => in_array($this->status, [self::STATUS_ORDERED, self::STATUS_PARTIAL, self::STATUS_RECEIVED], true),
            default                 => true,
        };
    }

    /**
     * Bayar dulu: finance baru boleh membayar setelah dana tercatat di finance;
     * penyetuju (bos) boleh membayar langsung tanpa lewat finance.
     */
    public function canPayBy(User $user): bool
    {
        if (!$this->canPay() || !$user->hasPermission('purchase', 'pay')) {
            return false;
        }

        return !$this->isPayFirst()
            || $this->fund_status === self::FUND_AT_FINANCE
            || $user->hasPermission('approval', 'decide');
    }

    public function canCancel(): bool
    {
        if ($this->payment_status !== self::PAYMENT_UNPAID) {
            return false;
        }

        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_APPROVED, self::STATUS_ORDERED], true)) {
            return true;
        }

        // Semua barang sudah diretur dan belum dibayar: tidak ada lagi yang perlu dibayar.
        return $this->status === self::STATUS_RECEIVED && $this->payableTotal() <= 0;
    }

    public function canDelete(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Nominal yang seharusnya dibayar: setelah diterima lengkap = barang yang tidak diretur
     * dengan harga aktual; sebelum itu = total PO.
     */
    public function payableTotal(): float
    {
        if ($this->status !== self::STATUS_RECEIVED) {
            return (float) $this->total;
        }

        return round($this->items->sum(fn (PurchaseItem $item) => max(0, $item->received_qty - $item->returned_qty) * $item->effective_price), 2);
    }

    /** Selisih harga aktual terhadap PO untuk barang yang sudah diterima (positif = lebih mahal). */
    public function priceVariance(): float
    {
        return round($this->items->sum(fn (PurchaseItem $item) => $item->received_qty * ($item->effective_price - (float) $item->unit_price)), 2);
    }

    /**
     * Status jatuh tempo untuk pembayaran tempo yang belum dibayar, atau null bila tidak relevan.
     *
     * @return array{days: int, overdue: bool, soon: bool}|null
     */
    public function dueState(): ?array
    {
        if ($this->payment_timing !== self::TIMING_CREDIT || !$this->due_date
            || $this->payment_status !== self::PAYMENT_UNPAID || !in_array($this->status, self::COMMITTED, true)) {
            return null;
        }

        $days = (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);

        return ['days' => $days, 'overdue' => $days < 0, 'soon' => $days <= self::DUE_SOON_DAYS];
    }

    /** Tahap jalur uang: Belum Dibayar, Dana diminta, Dana di finance, atau Dibayar. */
    public function getMoneyStageAttribute(): string
    {
        if ($this->payment_status === self::PAYMENT_PAID) {
            return self::PAYMENT_PAID;
        }

        return match ($this->fund_status) {
            self::FUND_REQUESTED  => 'Dana diminta',
            self::FUND_AT_FINANCE => 'Dana di finance',
            default               => self::PAYMENT_UNPAID,
        };
    }

    public function getMoneyBadgeAttribute(): string
    {
        return match ($this->money_stage) {
            self::PAYMENT_PAID => 'badge-soft-success',
            'Dana di finance'  => 'badge-soft-primary',
            default            => 'badge-soft-warning',
        };
    }

    /** Nomor WhatsApp vendor dalam format internasional, atau null bila tidak tersedia. */
    public function vendorWhatsapp(): ?string
    {
        $vendor = $this->vendor;

        if (!$vendor || !$vendor->phone || !$vendor->has_whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $vendor->phone);

        return $digits === '' ? null : preg_replace('/^0/', '62', $digits);
    }

    /** Pesan pesanan bawaan ke vendor (kode PO, item, total, tanggal); bisa diubah sebelum dikirim. */
    public function vendorMessage(): string
    {
        $lines = $this->items->map(fn ($item) => "- {$item->name}: {$item->qty} x " . Money::formatRupiah($item->unit_price))->all();

        return "Halo {$this->vendor_name}, kami ingin memesan sesuai {$this->code} tanggal "
            . $this->purchase_date->translatedFormat('d M Y') . ":\n"
            . implode("\n", $lines)
            . "\nTotal: " . Money::formatRupiah($this->total)
            . "\nMohon konfirmasi ketersediaan dan estimasi pengiriman. Terima kasih.";
    }
}

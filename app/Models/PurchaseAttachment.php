<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAttachment extends Model
{
    public const STAGE_INVOICE = 'invoice';
    public const STAGE_ORDER   = 'order';
    public const STAGE_RECEIPT = 'receipt';
    public const STAGE_FUND    = 'fund';
    public const STAGE_PAYMENT = 'payment';

    public const STAGES = [
        self::STAGE_INVOICE => 'Nota / Invoice vendor',
        self::STAGE_ORDER   => 'Bukti pesanan',
        self::STAGE_RECEIPT => 'Resi pengiriman',
        self::STAGE_FUND    => 'Bukti transfer dana',
        self::STAGE_PAYMENT => 'Bukti pembayaran',
    ];

    /** Tahap yang boleh ditambah manual lewat kartu Lampiran; sisanya lewat form tahapnya sendiri. */
    public const MANUAL_STAGES = [self::STAGE_INVOICE, self::STAGE_ORDER, self::STAGE_RECEIPT];

    protected $fillable = ['purchase_id', 'stage', 'path', 'original_name', 'uploaded_by'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }
}

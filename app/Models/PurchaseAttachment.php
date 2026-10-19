<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAttachment extends Model
{
    public const STAGE_INVOICE = 'nota';
    public const STAGE_ORDER   = 'order';
    public const STAGE_SHIPPING = 'resi';
    public const STAGE_FUND    = 'fund';
    public const STAGE_PAYMENT = 'payment';
    public const STAGE_OTHER   = 'other';

    public const STAGES = [
        self::STAGE_INVOICE  => 'Nota / invoice vendor',
        self::STAGE_ORDER    => 'Bukti pesanan',
        self::STAGE_SHIPPING => 'Resi pengiriman',
        self::STAGE_FUND     => 'Bukti transfer dana',
        self::STAGE_PAYMENT  => 'Bukti pembayaran',
        self::STAGE_OTHER    => 'Lainnya',
    ];

    /** Tahap yang boleh dipilih saat unggah manual (tahap lain terisi otomatis dari form aksinya). */
    public const MANUAL_STAGES = [self::STAGE_INVOICE, self::STAGE_SHIPPING, self::STAGE_OTHER];

    protected $fillable = ['purchase_id', 'stage', 'path', 'original_name', 'uploaded_by'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? 'Lainnya';
    }
}

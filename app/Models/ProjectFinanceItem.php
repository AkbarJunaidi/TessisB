<?php

namespace App\Models;

use App\Support\FinanceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buku kas: satu baris = satu transaksi masuk/keluar. project_id boleh kosong (gaji, operasional, dll).
 * source_type terisi bila baris dibuat otomatis oleh Pembelian atau Perbaikan Barang.
 */
class ProjectFinanceItem extends Model
{
    use HasFactory;

    public const SOURCE_PURCHASE = 'purchase';
    public const SOURCE_REPAIR   = 'repair';

    protected $table = 'project_finance_items';

    protected $fillable = [
        'project_id',
        'type',
        'amount',
        'tanggal',
        'category',
        'contact_id',
        'recipient',
        'payment_method',
        'source_type',
        'source_id',
        'created_by',
        'description',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            $item->tanggal ??= now()->toDateString();
            $item->category ??= $item->type === 'income' ? FinanceCategory::PROJECT_INCOME : FinanceCategory::PROJECT_EXPENSE;
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Tanpa baris milik project yang sudah dihapus (project_id kosong tetap ikut). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('project_id')->orWhereHas('project'));
    }

    /** Baris otomatis dari modul lain: ubah/hapus lewat modul asalnya. */
    public function isAuto(): bool
    {
        return $this->source_type !== null;
    }
}

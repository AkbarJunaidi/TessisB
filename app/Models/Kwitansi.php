<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kwitansi extends Model
{
    use HasFactory;

    protected $table = 'kwitansis';

    protected $fillable = [
        'nomor',
        'project_id',
        'created_by',
        'signature_id',
        'tanggal',
        'jumlah',
        'metode_pembayaran',
        'keterangan',
        'kategori_pembayaran',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah'  => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }
}

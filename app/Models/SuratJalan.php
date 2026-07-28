<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJalan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'surat_jalans';

    protected $fillable = [
        'nomor',
        'project_id',
        'created_by',
        'kepada',
        'pic',
        'tanggal_terbit',
        'tanggal_keberangkatan',
        'jam_berangkat',
        'tanggal_gladi_bersih',
        'waktu_gladi_bersih',
        'tanggal_acara',
        'tanggal_acara_selesai',
        'waktu_acara',
        'lokasi_acara',
        'catatan',
        'file_path',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SuratJalanItem::class);
    }
}

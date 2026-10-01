<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master Lokasi (kantor, gudang, lokasi event, dll). Dipakai untuk menempatkan unit barang
 * dan bisa dipakai ulang di form lain lewat endpoint autocomplete.
 */
class Location extends Model
{
    use HasFactory;

    public const JENIS = [
        'kantor'  => 'Kantor',
        'gudang'  => 'Gudang',
        'event'   => 'Lokasi Event',
        'lainnya' => 'Lainnya',
    ];

    public const DEFAULT_RADIUS_M = 100;

    protected $fillable = [
        'name',
        'jenis',
        'address',
        'latitude',
        'longitude',
        'radius_m',
        'can_store_units',
        'is_default',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'latitude'        => 'float',
        'longitude'       => 'float',
        'radius_m'        => 'integer',
        'can_store_units' => 'boolean',
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Unit yang tersimpan (lokasi sekarang) di lokasi ini.
     */
    public function unitsNow(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'lokasi_sekarang_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Lokasi yang boleh dipakai untuk menyimpan unit (kantor/gudang).
     */
    public function scopeStorage(Builder $query): Builder
    {
        return $query->where('can_store_units', true);
    }

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Koordinat sebagai teks "lat, lng" (tanpa nol di belakang); string kosong bila belum diisi.
     */
    public function coordinateText(): string
    {
        if (!$this->hasCoordinates()) {
            return '';
        }

        $fmt = fn (float $n) => rtrim(rtrim(number_format($n, 7, '.', ''), '0'), '.');

        return $fmt($this->latitude) . ', ' . $fmt($this->longitude);
    }

    /**
     * Tautan Google Maps dari koordinat (tanpa kunci API); null bila belum ada koordinat.
     */
    public function mapsUrl(): ?string
    {
        return $this->hasCoordinates()
            ? 'https://www.google.com/maps?q=' . $this->latitude . ',' . $this->longitude
            : null;
    }

    /**
     * Bentuk data lokasi untuk form/autocomplete (dipakai halaman Lokasi dan endpoint pencarian).
     */
    public function toFormArray(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'jenis'           => $this->jenis,
            'jenis_label'     => $this->jenis_label,
            'address'         => $this->address,
            'koordinat'       => $this->coordinateText(),
            'radius_m'        => $this->radius_m,
            'can_store_units' => $this->can_store_units,
            'is_default'      => $this->is_default,
            'is_active'       => $this->is_active,
            'notes'           => $this->notes,
        ];
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst((string) $this->jenis);
    }

    /**
     * Id lokasi awal untuk unit baru (null bila belum ada).
     */
    public static function defaultId(): ?int
    {
        $id = static::where('is_default', true)->value('id');

        return $id ? (int) $id : null;
    }
}

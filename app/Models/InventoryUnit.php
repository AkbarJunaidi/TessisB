<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryUnit extends Model
{
    use HasFactory;

    protected $table = 'inventory_units';

    protected $fillable = [
        'inventory_id',
        'unit_number',
        'status',
        'surat_jalan_item_id',
        'repair_item_id',
        'lokasi_utama_id',
        'lokasi_sekarang_id',
        'servis_terakhir_at',
        'pemakaian_sejak_servis',
    ];

    protected $casts = [
        'servis_terakhir_at' => 'date',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Baris Surat Jalan Item yang sedang meminjam unit ini (null kalau
     * unit sedang tidak dipinjam siapa pun / ada di gudang).
     */
    public function suratJalanItem(): BelongsTo
    {
        return $this->belongsTo(SuratJalanItem::class);
    }

    /**
     * Baris catatan Perbaikan Barang yang sedang berjalan untuk unit ini
     * (null kalau unit tidak sedang diservis).
     */
    public function repairItem(): BelongsTo
    {
        return $this->belongsTo(InventoryRepairItem::class, 'repair_item_id');
    }

    public function isInRepair(): bool
    {
        return !is_null($this->repair_item_id);
    }

    /**
     * Lokasi utama (tempat simpan semestinya) unit ini.
     */
    public function lokasiUtama(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'lokasi_utama_id');
    }

    /**
     * Lokasi unit ini terakhir disimpan. Untuk unit yang sedang dipinjam lewat Surat Jalan,
     * posisi sebenarnya adalah "di lapangan" - lihat isOnLoan().
     */
    public function lokasiSekarang(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'lokasi_sekarang_id');
    }

    public function isOnLoan(): bool
    {
        return !is_null($this->surat_jalan_item_id);
    }

    /**
     * Unit sedang berada di lokasi yang berbeda dari lokasi utamanya (dan tidak sedang dipinjam).
     */
    public function isOffHome(): bool
    {
        return !$this->isOnLoan()
            && $this->lokasi_utama_id !== null
            && $this->lokasi_sekarang_id !== null
            && (int) $this->lokasi_utama_id !== (int) $this->lokasi_sekarang_id;
    }

    /**
     * Status yang benar-benar ditampilkan ke user: kalau unit sedang
     * dipinjam (terhubung ke Surat Jalan Item yang belum dikembalikan),
     * tampilkan "Dipinjam" - mengalahkan status kondisi manual (Tersedia/dst),
     * karena posisi fisiknya memang sedang keluar.
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(fn () => $this->surat_jalan_item_id ? 'Dipinjam' : $this->status);
    }
}

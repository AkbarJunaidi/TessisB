<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Contact extends Model
{
    /**
     * Nama tabel yang dikelola di dalam database MySQL.
     */
    protected $table = 'contacts';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'name',
        'company',
        'phone',
        'has_whatsapp',
        'email',
        'address',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'has_whatsapp' => 'boolean',
    ];

    /**
     * Warna avatar (dipakai di card & detail) - dipilih deterministik dari
     * nama, supaya kontak yang sama selalu dapat warna yang sama.
     */
    protected const AVATAR_COLORS = [
        'primary', 'success', 'danger', 'warning', 'info', 'secondary', 'dark',
    ];

    /**
     * Relasi ke User pembuat/pencatat kontak ini (Belongs To).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Inisial nama (maks 2 huruf) untuk avatar di card & detail.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim($this->name));
            $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

            return implode('', $letters) ?: '?';
        });
    }

    /**
     * Warna avatar Bootstrap (primary/success/dst), deterministik dari nama.
     */
    protected function avatarColor(): Attribute
    {
        return Attribute::get(
            fn () => self::AVATAR_COLORS[crc32($this->name) % count(self::AVATAR_COLORS)]
        );
    }

    /**
     * Mencari Project yang kemungkinan besar adalah Project dari kontak
     * ini, dengan mencocokkan nama Kontak terhadap kolom `client` bebas-
     * teks di tabel projects (disamakan huruf kecil & tanpa spasi
     * berlebih). BUKAN relasi foreign key sungguhan - Kontak sengaja
     * dibuat berdiri sendiri, jadi pencocokan ini best-effort saja dan
     * tidak selalu akurat kalau penulisan nama client di Project beda
     * dengan nama di Kontak.
     */
    public function matchedProjects(): Collection
    {
        return Project::with('financeItems')
            ->whereRaw('LOWER(TRIM(client)) = ?', [mb_strtolower(trim($this->name))])
            ->latest()
            ->get();
    }

    /**
     * Total Pendapatan (real, dari financeItems tipe income) dari seluruh
     * Project yang cocok dengan nama Kontak ini. Lihat catatan di
     * matchedProjects() soal keterbatasan pencocokan nama.
     */
    public function getTotalIncomeAttribute(): float
    {
        return (float) $this->matchedProjects()->sum(fn (Project $project) => $project->total_income);
    }
}

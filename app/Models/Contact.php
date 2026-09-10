<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Relasi ASLI (foreign key, bukan tebak nama) ke Project - terisi
     * untuk Project yang field Client-nya dipilih dari saran autocomplete
     * (lihat ContactController::search() & form Create/Edit Project).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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
     * Mencari Project milik kontak ini. Ada 2 sumber, digabung:
     *
     * 1. Link ASLI (contact_id) - Project yang field Client-nya dipilih
     *    dari saran autocomplete saat dibuat/diedit. Akurat 100%.
     * 2. Fallback pencocokan nama (best-effort, seperti sebelumnya) -
     *    HANYA untuk Project LAMA yang belum/tidak di-link (contact_id
     *    masih null), supaya tidak dobel hitung dengan sumber #1 di atas.
     *
     * Sumber #2 ini pelan-pelan akan makin jarang terpakai begitu makin
     * banyak Project baru dibuat lewat autocomplete (sumber #1).
     */
    public function matchedProjects(): Collection
    {
        $linkedProjects = $this->projects()->with('financeItems')->latest()->get();

        $nameMatchedProjects = Project::with('financeItems')
            ->whereNull('contact_id')
            ->whereRaw('LOWER(TRIM(client)) = ?', [mb_strtolower(trim($this->name))])
            ->latest()
            ->get();

        return $linkedProjects
            ->concat($nameMatchedProjects)
            ->sortByDesc('created_at')
            ->values();
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

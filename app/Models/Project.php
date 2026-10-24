<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $casts = [
        'deadline'    => 'date',
        'event_date'  => 'date',
        'event_end_date' => 'date',
        'board_lists' => 'array',
        'estimated_value' => 'decimal:2',
        'budget'          => 'decimal:2',
    ];

    /**
     * Daftar list/kolom board default kalau project belum pernah custom.
     */
    public const DEFAULT_BOARD_LISTS = [
        ['label' => 'Todo', 'color' => 'secondary'],
        ['label' => 'In Progress', 'color' => 'primary'],
        ['label' => 'Review', 'color' => 'warning'],
        ['label' => 'Done', 'color' => 'success'],
    ];

    /**
     * Warna kolom untuk halaman Pipeline (dikelompokkan berdasarkan kolom
     * `status` yang sudah ada - lihat ProjectController::updateStatus()).
     * Urutan array ini juga menentukan urutan kolom pada papan Pipeline.
     */
    public const PIPELINE_STATUS_COLORS = [
        'Draft'        => 'secondary',
        'Scheduled'    => 'info',
        'Confirmed'    => 'primary',
        'In Progress'  => 'warning',
        'On Review'    => 'dark',
        'Done'         => 'success',
    ];

    /**
     * Label Bahasa Indonesia untuk kolom `status` - HANYA untuk tampilan
     * (dropdown stepper, filter, papan Pipeline). Nilai asli yang disimpan
     * di database TETAP dalam Bahasa Inggris (Draft, Scheduled, dst) agar
     * validasi (ProjectController::updateStatus()) dan query yang sudah
     * ada (mis. ProjectService::getStats()) tidak perlu diubah.
     */
    public const STATUS_LABELS = [
        'Draft'        => 'Rancangan',
        'Scheduled'    => 'Negosiasi',
        'Confirmed'    => 'Deal',
        'In Progress'  => 'Berjalan',
        'On Review'    => 'Review',
        'Done'         => 'Selesai',
    ];

    /** Panduan singkat per status: apa yang perlu dilakukan sebelum lanjut ke tahap berikutnya. */
    public const STATUS_GUIDE = [
        'Draft'       => 'Lengkapi data client, tanggal, dan lokasi acara. Saat mulai bernegosiasi, lanjut ke Negosiasi.',
        'Scheduled'   => 'Selesaikan penawaran dengan client. Bila sudah sepakat, lanjut ke Deal.',
        'Confirmed'   => 'Siapkan alat (Booking Alat) dan kru. Saat acara dimulai, lanjut ke Berjalan.',
        'In Progress' => 'Pastikan surat jalan dan kru sesuai. Setelah acara selesai, lanjut ke Review.',
        'On Review'   => 'Cek alat sudah kembali, lalu catat pembayaran dan pengeluaran. Bila beres, lanjut ke Selesai.',
        'Done'        => 'Project sudah selesai.',
    ];

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'board_lists',
        'created_by',
        'client',
        'contact_id',
        'pic',
        'company',
        'email',
        'phone',
        'category',
        'event_date',
        'event_end_date',
        'event_time_start',
        'event_time_end',
        'location',
        'address',
        'estimated_duration_minutes',
        'priority',
        'status',
        'estimated_value',
        'budget',
        'deleted_by',
    ];

    /**
     * Relasi ke seluruh item Pendapatan & Pengeluaran project ini.
     */
    public function financeItems(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class);
    }

    /** Batas karakter nama project di form Create/Edit. */
    public const NAME_MAX_LENGTH = 40;

    /** Batas karakter nama project di tampilan selain halaman detail; sisanya diganti "...". */
    public const NAME_DISPLAY_LIMIT = 25;

    /** Status berikutnya menurut urutan STATUS_LABELS, atau null bila sudah di tahap terakhir. */
    public function nextStatus(): ?string
    {
        $keys = array_keys(self::STATUS_LABELS);
        $at = array_search($this->status, $keys, true);

        return $at === false ? null : ($keys[$at + 1] ?? null);
    }

    public static function shorten(?string $name): string
    {
        return \Illuminate\Support\Str::limit((string) $name, self::NAME_DISPLAY_LIMIT, '...');
    }

    /** Nama singkat untuk daftar, tabel, dan label (halaman detail memakai $project->name penuh). */
    protected function shortName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => self::shorten($this->name));
    }

    /**
     * Total Pendapatan (jumlah seluruh baris item bertipe income).
     */
    protected function totalIncome(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => (float) $this->financeItems->where('type', 'income')->sum('amount')
        );
    }

    /**
     * Pemasukan dari client saja (tanpa uang kembali pembelian); dasar Total Diterima dan Sisa.
     */
    protected function totalClientIncome(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => (float) $this->financeItems
                ->where('type', 'income')
                ->filter(fn ($item) => $item->category !== \App\Support\FinanceCategory::PURCHASE_REFUND)
                ->sum('amount')
        );
    }

    /**
     * Total Pengeluaran (jumlah seluruh baris item bertipe expense).
     */
    protected function totalExpense(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => (float) $this->financeItems->where('type', 'expense')->sum('amount')
        );
    }

    /**
     * Laba bersih project ini (Total Pendapatan - Total Pengeluaran).
     */
    protected function profit(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->total_income - $this->total_expense
        );
    }

    /**
     * Daftar list/kolom Kanban board project ini (custom kalau pernah
     * ditambah lewat "Add List", atau 4 default kalau belum pernah).
     */
    public function getBoardLists(): array
    {
        return $this->board_lists ?: self::DEFAULT_BOARD_LISTS;
    }

    /**
     * Menambahkan list/kolom board baru (dipanggil dari fitur "Add List").
     */
    public function addBoardList(string $label, string $color = 'secondary'): void
    {
        $lists = $this->getBoardLists();
        $lists[] = ['label' => $label, 'color' => $color];

        $this->update(['board_lists' => $lists]);
    }

    /**
     * Relasi One-to-Many: Sebuah Project memiliki banyak Tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Relasi BelongsTo: Proyek ini diinisiasi oleh seorang User.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi BelongsTo: Kontak (client) yang benar-benar terhubung ke
     * project ini - terisi HANYA kalau field Client dipilih dari saran
     * autocomplete (bukan diketik bebas). Nullable - project tetap boleh
     * punya client yang belum/tidak ada di daftar Kontak.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Relasi One-to-Many: Crew/Tim Project. Nama diinput bebas sebagai teks
     * (lihat ProjectCrew) - TIDAK harus user terdaftar di sistem.
     */
    public function crews(): HasMany
    {
        return $this->hasMany(ProjectCrew::class);
    }

    /**
     * Relasi One-to-Many: Catatan pada project.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    /**
     * Relasi One-to-One: Folder khusus Document Center milik project ini.
     */
    public function folder(): HasOne
    {
        return $this->hasOne(Folder::class);
    }

    /**
     * Relasi One-to-Many: Surat Jalan yang diterbitkan untuk project ini.
     */
    public function suratJalans(): HasMany
    {
        return $this->hasMany(SuratJalan::class);
    }

    /**
     * Relasi One-to-Many: Kwitansi (bukti pembayaran) project ini.
     */
    public function kwitansis(): HasMany
    {
        return $this->hasMany(Kwitansi::class);
    }

    /**
     * Relasi One-to-Many: Booking alat (rencana pemakaian, belum tentu
     * jadi Surat Jalan) untuk project ini - lihat EquipmentBooking.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(EquipmentBooking::class);
    }

    /**
     * Total semua Kwitansi berstatus Aktif (BEDA dari totalDiterima di bawah,
     * yang dihitung dari buku kas).
     */
    protected function totalDibayar(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => (float) $this->kwitansis->where('status', 'Aktif')->sum('jumlah')
        );
    }

    /**
     * Uang yang sudah diterima = baris buku kas dari Kwitansi + yang terbesar antara pemasukan manual dan
     * Kwitansi lama (belum punya baris; pembayaran yang sama bisa tercatat di keduanya). Satu-satunya rumus.
     */
    public static function combinePayments(float $fromKwitansi, float $manual, float $legacyKwitansi): float
    {
        return $fromKwitansi + max($manual, $legacyKwitansi);
    }

    /**
     * Rincian total diterima dari data yang sudah dimuat (dipakai juga oleh PDF arsip).
     *
     * @param  Collection<int, ProjectFinanceItem>  $financeItems
     * @param  Collection<int, Kwitansi>  $kwitansis
     * @return array{kwitansi: float, manual: float, legacy: float, received: float}
     */
    public static function breakdownFrom(Collection $financeItems, Collection $kwitansis): array
    {
        $clientIncome = $financeItems->where('type', 'income')
            ->filter(fn ($item) => $item->category !== \App\Support\FinanceCategory::PURCHASE_REFUND);
        $isKwitansi = fn ($item) => $item->source_type === ProjectFinanceItem::SOURCE_KWITANSI;
        $linkedIds = $financeItems->filter($isKwitansi)->pluck('source_id')->all();

        $fromKwitansi = (float) $clientIncome->filter($isKwitansi)->sum('amount');
        $manual = (float) $clientIncome->reject($isKwitansi)->sum('amount');
        $legacy = (float) $kwitansis->where('status', 'Aktif')->whereNotIn('id', $linkedIds)->sum('jumlah');

        return [
            'kwitansi' => $fromKwitansi,
            'manual'   => $manual,
            'legacy'   => $legacy,
            'received' => self::combinePayments($fromKwitansi, $manual, $legacy),
        ];
    }

    /**
     * Menambah kolom pay_kwitansi, pay_manual, pay_legacy sebagai agregat database (tanpa N+1);
     * dibaca lewat paymentBreakdown().
     */
    public function scopeWithPaymentTotals(Builder $query): Builder
    {
        return $query
            ->withSum(['financeItems as pay_kwitansi' => fn ($q) => $q->kwitansiPayments()], 'amount')
            ->withSum(['financeItems as pay_manual' => fn ($q) => $q->manualClientPayments()], 'amount')
            ->withSum(['kwitansis as pay_legacy' => fn ($q) => $q->where('status', 'Aktif')->whereDoesntHave('financeItem')], 'jumlah');
    }

    /** @return array{kwitansi: float, manual: float, legacy: float, received: float} */
    public function paymentBreakdown(): array
    {
        if (array_key_exists('pay_kwitansi', $this->attributes)) {
            $fromKwitansi = (float) $this->attributes['pay_kwitansi'];
            $manual = (float) ($this->attributes['pay_manual'] ?? 0);
            $legacy = (float) ($this->attributes['pay_legacy'] ?? 0);

            return [
                'kwitansi' => $fromKwitansi,
                'manual'   => $manual,
                'legacy'   => $legacy,
                'received' => self::combinePayments($fromKwitansi, $manual, $legacy),
            ];
        }

        return self::breakdownFrom($this->financeItems, $this->kwitansis);
    }

    protected function totalDiterima(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->paymentBreakdown()['received']
        );
    }

    /**
     * Sisa yang belum dibayar (Estimasi Pendapatan - Total Diterima, tidak
     * pernah negatif).
     */
    protected function sisaPembayaran(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => max(0, (float) $this->estimated_value - $this->total_diterima)
        );
    }

    /**
     * Label status pembayaran project ini untuk halaman Keuangan & tab Kwitansi.
     */
    protected function statusPembayaran(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            if ((float) $this->estimated_value <= 0) {
                return 'Belum Ada Tagihan';
            }

            return $this->total_diterima >= (float) $this->estimated_value ? 'Lunas' : 'Belum Lunas';
        });
    }

    /**
     * Relasi BelongsTo: User yang melakukan penghapusan (untuk fitur Trash).
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

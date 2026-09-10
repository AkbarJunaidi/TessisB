<?php

namespace App\Models;

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
        'deleted_by',
    ];

    /**
     * Relasi ke seluruh item Pendapatan & Pengeluaran project ini.
     */
    public function financeItems(): HasMany
    {
        return $this->hasMany(ProjectFinanceItem::class);
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
     * Relasi BelongsTo: User yang melakukan penghapusan (untuk fitur Trash).
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

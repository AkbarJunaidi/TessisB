<?php

namespace App\Services\Dashboard;

use App\Models\Contact;
use App\Models\File;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\Task;
use App\Models\User;
use App\Services\Report\FinancialReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        protected FinancialReportService $financialReportService
    ) {}

    /**
     * Lama cache statistik Dashboard (detik). Kartu statistik ini dibuka
     * berkali-kali oleh siapa pun yang login (halaman pertama setelah
     * login) - tanpa cache, tiap kunjungan menjalankan beberapa query COUNT()
     * terpisah. Angka ringkasan seperti ini tidak perlu 100% real-time
     * detik-ke-detik, jadi cache pendek dipakai untuk mengurangi beban query
     * tanpa terasa basi bagi user.
     *
     * Nilai di dalamnya TIDAK bergantung pada siapa yang login (hitungan
     * global), jadi 1 cache ini aman dipakai bersama oleh semua role -
     * penyaringan "siapa boleh lihat kartu apa" dilakukan belakangan
     * di Controller/View berdasarkan hasPermission(), bukan di sini.
     */
    private const CACHE_SECONDS = 60;

    /**
     * Mengambil seluruh statistik Dashboard (angka global, sama untuk semua role).
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            'dashboard.statistics',
            self::CACHE_SECONDS,
            fn () => [
                'total_inventory'          => Inventory::count(),
                'total_project'            => Project::count(),
                'total_task'               => Task::count(),
                'total_files'              => File::count(),
                'total_user'               => User::count(),
                'total_contact'            => Contact::count(),
                'new_users_this_month'     => $this->createdThisMonth(User::query()),
                'new_projects_this_month'  => $this->createdThisMonth(Project::query()),
                'new_inventory_this_month' => $this->createdThisMonth(Inventory::query()),
                'new_contacts_this_month'  => $this->createdThisMonth(Contact::query()),
            ]
        );
    }

    /**
     * Helper hitung baris yang `created_at`-nya bulan & tahun ini - dipakai
     * berulang untuk badge "+N baru bulan ini" di beberapa kartu statistik,
     * supaya tidak menulis ulang whereYear()/whereMonth() tiap kali.
     */
    private function createdThisMonth(\Illuminate\Database\Eloquent\Builder $query): int
    {
        return $query
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    /**
     * Jumlah unit Inventory yang sedang dipinjam saat ini (terhubung ke Surat
     * Jalan Item yang belum dikembalikan) - lihat InventoryUnit::displayStatus().
     * Angka global (tidak bergantung user), aman di-cache singkat seperti statistik lain.
     */
    public function getBorrowedUnitsCount(): int
    {
        return Cache::remember(
            'dashboard.borrowed_units_count',
            self::CACHE_SECONDS,
            fn () => InventoryUnit::whereNotNull('surat_jalan_item_id')->count()
        );
    }

    /**
     * Ringkasan Data Keuangan untuk kartu Dashboard: Pendapatan bulan ini
     * (reuse logic dari FinancialReportService::getMonthlySummary() - TIDAK
     * lewat validateMonthIsComplete()/generateMonthlyPdf(), karena kartu ini
     * cuma menampilkan angka, bukan menerbitkan laporan resmi) dan total
     * Estimasi Pendapatan dari seluruh project yang masih berjalan (belum
     * "Done") - field tampilan saja, bukan pendapatan riil, label di View
     * dibuat jelas supaya tidak disalahartikan sebagai angka yang sama.
     */
    public function getFinanceSummary(): array
    {
        $monthlySummary = $this->financialReportService->getMonthlySummary(
            now()->month,
            now()->year
        );

        return [
            'revenue_this_month'       => $monthlySummary['total_revenue'],
            'pipeline_estimated_value' => Project::where('status', '!=', 'Done')
                ->sum('estimated_value'),
        ];
    }

    /**
     * Task milik user yang login (assigned_to), diurutkan dari deadline
     * paling dekat, untuk kartu "Task Saya" - prioritas utama Dashboard
     * karena sebelumnya tidak ada satu pun kartu yang relevan buat Employee.
     *
     * Catatan/limitasi yang disengaja: kolom board Task per Project bisa
     * di-custom namanya (lihat Project::getBoardLists()/addBoardList()),
     * jadi tidak ada nilai "selesai" yang benar-benar baku di seluruh
     * sistem. Filter di bawah memakai daftar label umum (default board
     * "Done" & label Pipeline "Selesai") sebagai heuristik terbaik yang
     * masuk akal tanpa membuat sistem status baru - kalau sebuah Project
     * mengganti nama kolom "Done"-nya jadi istilah lain, task yang sudah
     * kelar di kolom itu bisa saja masih muncul di sini. Cukup aman untuk
     * kartu ringkasan, tapi didokumentasikan agar tidak dianggap 100% akurat.
     */
    public function getMyTasks(User $user, int $limit = 5): Collection
    {
        return Task::query()
            ->where('assigned_to', $user->id)
            ->whereRaw('LOWER(status) NOT IN (?, ?)', ['done', 'selesai'])
            ->with('project:id,name')
            ->orderBy('deadline')
            ->take($limit)
            ->get();
    }

    /**
     * Project dengan Tanggal Acara (event_date) dalam rentang hari ini s/d
     * $daysAhead hari ke depan (default 7 hari), diurutkan dari yang paling
     * dekat - untuk kartu "Project Hari Ini & Mendatang". Sengaja dibatasi
     * rentang tanggal (bukan cuma dipotong $limit baris) supaya daftar ini
     * benar-benar mencerminkan "yang mau terjadi dalam waktu dekat" - kalau
     * cuma dipotong jumlah baris, project yang jaraknya masih berbulan-bulan
     * bisa ikut muncul kalau kebetulan project terjadwal sedang sedikit.
     */
    public function getUpcomingProjects(int $daysAhead = 7, int $limit = 5): Collection
    {
        return Project::query()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereDate('event_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->orderBy('event_date')
            ->take($limit)
            ->get(['id', 'name', 'client', 'event_date', 'status']);
    }

    /**
     * Surat Jalan yang paling baru dibuat (lintas semua Project) - untuk
     * kartu "Surat Jalan Terbaru". Tidak ada halaman listing global untuk
     * Surat Jalan di app ini (hanya per-Project), jadi kartu ini juga
     * berfungsi sebagai satu-satunya tempat melihat Surat Jalan lintas
     * Project tanpa harus masuk ke tiap Project satu-satu.
     */
    public function getRecentSuratJalan(int $limit = 5): Collection
    {
        return SuratJalan::query()
            ->with('project:id,name')
            ->latest()
            ->take($limit)
            ->get();
    }
}

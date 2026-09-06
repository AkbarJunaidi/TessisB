<?php

namespace App\Services\Notification;

use App\Models\Project;
use App\Models\ReportExport;
use App\Services\Auth\PasswordResetRequestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Notifikasi ringkas untuk navbar (Super Admin & Admin saja).
 *
 * Semua notifikasi di sini dihitung LANGSUNG dari data (bukan disimpan di
 * tabel tersendiri) - jadi selalu akurat mengikuti kondisi terbaru dan tidak
 * perlu mekanisme read/unread/cleanup terpisah untuk dirawat jangka panjang.
 *
 * Cara menambah jenis notifikasi baru di masa depan:
 * 1. Buat 1 method builder baru (private, meniru pola 2 method di bawah).
 * 2. Panggil method itu di getActiveNotifications().
 * Tidak ada bagian lain dari sistem ini yang perlu diubah.
 */
class NotificationService
{
    /** Berapa hari ke depan dianggap "mendekati deadline" (H-3). */
    private const DEADLINE_WARNING_DAYS = 3;

    /** Notifikasi "pendapatan belum diisi" hanya tampil H-5 menjelang akhir bulan. */
    private const MONTH_END_WARNING_DAYS = 5;

    /** Batas maksimum notifikasi yang ditampilkan per jenis, supaya navbar tidak banjir. */
    private const MAX_PER_TYPE = 10;

    /**
     * Lama cache notifikasi (detik). Endpoint ini di-poll browser tiap 60
     * detik oleh SETIAP tab Super Admin/Admin yang sedang login - tanpa
     * cache, setiap poll menjalankan ulang beberapa query (termasuk
     * whereDoesntHave) untuk semua orang yang online di saat bersamaan.
     * 30 detik dipilih supaya data tetap terasa "real-time" tapi query berat
     * di atas hanya benar-benar jalan ke database maksimal 2x per menit,
     * berapa pun banyaknya admin yang online.
     */
    private const CACHE_SECONDS = 30;

    protected PasswordResetRequestService $passwordResetRequestService;

    public function __construct(PasswordResetRequestService $passwordResetRequestService)
    {
        $this->passwordResetRequestService = $passwordResetRequestService;
    }

    /**
     * Seluruh notifikasi aktif saat ini, urut dari yang paling mendesak.
     *
     * Hasilnya sama untuk semua Super Admin/Admin (bukan notifikasi
     * per-user), jadi aman dipakai 1 cache key global.
     *
     * @return array<int, array{id: string, type: string, icon: string, title: string, message: string, url: string}>
     */
    public function getActiveNotifications(): array
    {
        return Cache::remember(
            'notifications.active',
            self::CACHE_SECONDS,
            fn () => [
                ...$this->getPendingPasswordResetNotifications(),
                ...$this->getReadyReportNotifications(),
                ...$this->getUnpaidNearDeadlineNotifications(),
                ...$this->getFinanceNotFilledThisMonthNotifications(),
            ]
        );
    }

    /**
     * Notifikasi 0: Permintaan "Lupa Password" yang belum ditindaklanjuti.
     * Diletakkan paling atas karena menyangkut akses akun pengguna lain -
     * lebih mendesak dibanding notifikasi operasional lainnya.
     */
    private function getPendingPasswordResetNotifications(): array
    {
        $requests = $this->passwordResetRequestService->pendingWithUser(self::MAX_PER_TYPE);

        return $requests->map(fn ($request) => [
            'id'      => "password-reset-{$request->id}",
            'type'    => 'password_reset_request',
            'icon'    => 'bi-key text-warning',
            'title'   => 'Permintaan Lupa Password',
            'message' => "{$request->user->name} ({$request->user->email}) minta reset password",
            'url'     => route('users.show', $request->user_id),
        ])->all();
    }

    /**
     * Notifikasi 0b: Laporan PDF (mis. Laporan Massal Inventory) yang sudah
     * selesai diproses di background (queue) tapi belum pernah diunduh.
     */
    private function getReadyReportNotifications(): array
    {
        $reports = ReportExport::readyAndUndownloaded()
            ->latest()
            ->limit(self::MAX_PER_TYPE)
            ->get();

        return $reports->map(fn ($report) => [
            'id'      => "report-{$report->id}",
            'type'    => 'report_ready',
            'icon'    => 'bi-file-earmark-arrow-down text-success',
            'title'   => 'Laporan Siap Diunduh',
            'message' => 'Laporan diproses ' . $report->created_at->format('d M Y H:i') . ', siap diunduh',
            'url'     => route('inventory.download-queued-report', $report),
        ])->all();
    }

    /**
     * Notifikasi 1: Project dengan deadline H-3 (hari ini s.d. 3 hari lagi)
     * tapi belum ada satupun data Pendapatan yang diisi di Data Keuangannya
     * - dianggap "client belum lunas".
     */
    private function getUnpaidNearDeadlineNotifications(): array
    {
        $today     = Carbon::today();
        $limitDate = $today->copy()->addDays(self::DEADLINE_WARNING_DAYS);

        $projects = Project::query()
            ->select(['id', 'name', 'client', 'deadline'])
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$today, $limitDate])
            ->whereDoesntHave('financeItems', fn ($query) => $query->where('type', 'income'))
            ->orderBy('deadline')
            ->limit(self::MAX_PER_TYPE)
            ->get();

        return $projects->map(function (Project $project) use ($today) {
            $daysLeft = (int) $today->diffInDays($project->deadline, false);
            $when     = $daysLeft <= 0 ? 'deadline hari ini' : "H-{$daysLeft} deadline";

            return [
                'id'      => "unpaid-{$project->id}",
                'type'    => 'unpaid_deadline',
                'icon'    => 'bi-cash-coin text-danger',
                'title'   => 'Client Belum Lunas',
                'message' => "{$project->client} - {$project->name} ({$when})",
                'url'     => route('projects.show', $project),
            ];
        })->all();
    }

    /**
     * Notifikasi 2: Project dengan tanggal acara di bulan berjalan tapi
     * belum ada satupun data Pendapatan yang diisi di Data Keuangannya.
     *
     * Notifikasi ini SENGAJA hanya tampil H-5 menjelang akhir bulan (bukan
     * sepanjang bulan) - supaya tidak mengganggu di awal/tengah bulan saat
     * pengisian data keuangan memang belum mendesak.
     */
    private function getFinanceNotFilledThisMonthNotifications(): array
    {
        $now = Carbon::now();
        $daysUntilMonthEnd = (int) $now->diffInDays($now->copy()->endOfMonth());

        if ($daysUntilMonthEnd > self::MONTH_END_WARNING_DAYS) {
            return [];
        }

        $projects = Project::query()
            ->select(['id', 'name', 'client', 'event_date'])
            ->whereNotNull('event_date')
            ->whereMonth('event_date', $now->month)
            ->whereYear('event_date', $now->year)
            ->whereDoesntHave('financeItems', fn ($query) => $query->where('type', 'income'))
            ->orderBy('event_date')
            ->limit(self::MAX_PER_TYPE)
            ->get();

        $when = $daysUntilMonthEnd <= 0 ? 'akhir bulan ini' : "H-{$daysUntilMonthEnd} akhir bulan";

        return $projects->map(fn (Project $project) => [
            'id'      => "finance-{$project->id}",
            'type'    => 'finance_missing',
            'icon'    => 'bi-clipboard-x text-warning',
            'title'   => 'Pendapatan Belum Diisi',
            'message' => "{$project->name} - pendapatan bulan ini belum diisi ({$when})",
            'url'     => route('projects.show', $project),
        ])->all();
    }
}

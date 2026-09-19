<?php

namespace App\Services\Notification;

use App\Models\NotificationState;
use App\Models\NotificationTypeSetting;
use App\Models\Project;
use App\Models\ReportExport;
use App\Notifications\AnnouncementNotification;
use App\Services\Auth\PasswordResetRequestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Notifikasi ringkas untuk navbar (Super Admin & Admin saja).
 *
 * Sebagian besar (4 dari 5 jenis) dihitung LANGSUNG dari data (bukan
 * disimpan di tabel tersendiri) - selalu akurat, tidak perlu mekanisme
 * read/unread/cleanup terpisah, dan SAMA untuk semua orang sehingga aman
 * di-cache 1 key global seperti semula. 1 jenis ("announcement",
 * pengumuman dari Super Admin - lihat AnnouncementNotification) SUNGGUH
 * tersimpan per-user di tabel `notifications` bawaan Laravel, jadi
 * BEDA per user yang login - jenis ini SENGAJA tidak masuk cache global
 * itu, dihitung fresh tiap request (lihat getActiveNotifications() untuk
 * pembagian strategi cache-nya).
 *
 * Jenis mana yang aktif ditampilkan & urutannya diatur Super Admin lewat
 * halaman Notifikasi (tabel notification_type_settings, lihat
 * NotificationSettingController) - method getTypeConfig() di bawah yang
 * membaca pengaturan itu.
 *
 * Cara menambah jenis notifikasi baru di masa depan:
 * 1. Buat 1 method builder baru (private, meniru pola di bawah).
 * 2. Tambahkan entri barunya ke array di getActiveNotifications().
 * 3. Tambahkan label-nya ke TYPE_LABELS.
 * Tidak perlu migration baru - jenis yang belum ada baris settingnya
 * otomatis dianggap enabled=true, tampil paling akhir (lihat getTypeConfig()).
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

    /**
     * Label yang tampil di panel "Kelola Notifikasi Otomatis" (halaman
     * Notifikasi) - dibaca NotificationSettingController, bukan di sini.
     */
    public const TYPE_LABELS = [
        'password_reset_request' => 'Permintaan Lupa Password',
        'report_ready'           => 'Laporan Siap Diunduh',
        'unpaid_deadline'        => 'Client Belum Lunas (H-3 Deadline)',
        'finance_missing'        => 'Pendapatan Belum Diisi (H-5 Akhir Bulan)',
        'announcement'           => 'Pengumuman dari Super Admin',
    ];

    protected PasswordResetRequestService $passwordResetRequestService;

    public function __construct(PasswordResetRequestService $passwordResetRequestService)
    {
        $this->passwordResetRequestService = $passwordResetRequestService;
    }

    /**
     * Seluruh notifikasi aktif saat ini untuk 1 user, urut sesuai
     * pengaturan Super Admin.
     *
     * Strategi cache SENGAJA dipecah 2 bagian:
     * - 4 jenis yang query-nya berat & SAMA untuk semua orang (password
     *   reset, report ready, unpaid deadline, finance missing) di-cache
     *   GLOBAL 30 detik seperti desain semula - tidak terpengaruh
     *   siapa yang login.
     * - Jenis "announcement" (per-user) DAN filter enabled/urutan (dari
     *   notification_type_settings, yang bisa diubah Super Admin kapan
     *   saja) dihitung FRESH tiap request - keduanya murah (1 query
     *   indexed + operasi array biasa), sehingga TIDAK perlu invalidasi
     *   cache per-user yang rumit (driver cache 'database' project ini
     *   tidak mendukung cache tags seperti Redis) - perubahan pengaturan
     *   langsung terasa di poll berikutnya, bukan menunggu cache 30 detik.
     *
     * @return array<int, array{id: string, type: string, icon: string, title: string, message: string, url: string}>
     */
    public function getActiveNotifications(int $userId): array
    {
        $typeConfig = $this->getTypeConfig();

        $grouped = Cache::remember(
            'notifications.active.shared',
            self::CACHE_SECONDS,
            fn () => [
                'password_reset_request' => $this->getPendingPasswordResetNotifications(),
                'report_ready'           => $this->getReadyReportNotifications(),
                'unpaid_deadline'        => $this->getUnpaidNearDeadlineNotifications(),
                'finance_missing'        => $this->getFinanceNotFilledThisMonthNotifications(),
            ]
        );

        $grouped['announcement'] = $this->getAnnouncementNotifications($userId);

        // Employee HANYA boleh lihat jenis "announcement" - 4 jenis lain
        // berisi data administratif/keuangan (permintaan reset password,
        // client belum lunas, dst) yang bukan urusan Employee. Ini
        // hardcode berdasar ROLE, terpisah dari pengaturan enabled/
        // sort_order Super Admin di bawah (yang cuma relevan buat
        // Super Admin/Admin, karena Employee memang tidak pernah lihat
        // 4 jenis itu apa pun settingnya).
        $user = \App\Models\User::find($userId);
        if (!$user?->hasRole('super_admin', 'admin')) {
            $grouped = array_intersect_key($grouped, ['announcement' => true]);
        }

        // Buang jenis yang Super Admin nonaktifkan - dicek di sini
        // (bukan sebelum query di atas) demi kesederhanaan cache di atas;
        // biayanya cuma perbandingan array biasa, bukan query tambahan.
        $grouped = array_filter(
            $grouped,
            fn ($items, $type) => $typeConfig[$type]['enabled'] ?? true,
            ARRAY_FILTER_USE_BOTH
        );

        // Urutkan GRUP-nya (bukan item di dalamnya) sesuai sort_order dari
        // pengaturan Super Admin, lalu gabungkan jadi 1 array flat.
        uksort($grouped, fn ($a, $b) => ($typeConfig[$a]['sort_order'] ?? 999) <=> ($typeConfig[$b]['sort_order'] ?? 999));

        $items = $grouped ? array_merge(...array_values($grouped)) : [];

        // Terapkan sematan (pribadi per user) & penghapusan (GLOBAL, lihat
        // applyUserNotificationStates()) terhadap 4 jenis notifikasi
        // OTOMATIS - jenis "announcement" dilewati, itu sudah punya
        // mekanisme sematkan/hapus sendiri di AnnouncementService.
        return $this->applyUserNotificationStates($userId, $items);
    }

    /**
     * Buang item yang sudah DIHAPUS - GLOBAL untuk semua user, bukan cuma
     * yang menghapus (lihat deleteSystemNotification()) - dan angkat yang
     * disematkan (preferensi PRIBADI, tetap per user) ke paling atas.
     * Jenis "announcement" dilewati (lihat komentar di getActiveNotifications()).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function applyUserNotificationStates(int $userId, array $items): array
    {
        $keys = array_column(
            array_filter($items, fn ($item) => $item['type'] !== 'announcement'),
            'id'
        );

        if (empty($keys)) {
            return $items;
        }

        // Dicek TANPA filter user_id - sekali dihapus oleh siapa pun yang
        // berhak, notifikasinya hilang untuk semua orang yang melihatnya.
        $deletedKeys = NotificationState::whereIn('notification_key', $keys)
            ->whereNotNull('dismissed_at')
            ->pluck('notification_key')
            ->all();

        // Sematan tetap preferensi pribadi, jadi tetap discoped ke user ini.
        $pinnedKeys = NotificationState::where('user_id', $userId)
            ->whereIn('notification_key', $keys)
            ->whereNotNull('pinned_at')
            ->pluck('notification_key')
            ->all();

        $items = array_values(array_filter($items, function ($item) use ($deletedKeys) {
            return $item['type'] === 'announcement' || !in_array($item['id'], $deletedKeys, true);
        }));

        foreach ($items as &$item) {
            $item['pinned'] = $item['type'] !== 'announcement'
                && in_array($item['id'], $pinnedKeys, true);
        }
        unset($item);

        // Stable sort (dijamin PHP 8+) - yang disematkan naik ke atas,
        // urutan relatif lainnya (hasil sort_order jenis di atas) tetap
        // dipertahankan seperti semula.
        usort($items, fn ($a, $b) => ($b['pinned'] ?? false) <=> ($a['pinned'] ?? false));

        return $items;
    }

    /**
     * Baca pengaturan enabled/sort_order semua jenis dari database, 1x
     * query, dikembalikan sebagai array asosiatif keyed by type. Jenis
     * yang BELUM punya baris setting (misal ditambahkan programmer lewat
     * kode tapi migration seed-nya belum jalan) dianggap enabled=true,
     * sort_order 999 (tampil paling akhir) - lihat pemakaiannya di
     * buildActiveNotifications() via operator ?? di atas.
     *
     * @return array<string, array{enabled: bool, sort_order: int}>
     */
    private function getTypeConfig(): array
    {
        return NotificationTypeSetting::all()
            ->keyBy('type')
            ->map(fn ($row) => ['enabled' => $row->enabled, 'sort_order' => $row->sort_order])
            ->all();
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

    /**
     * Notifikasi 3 (BARU): Pengumuman dari Super Admin yang belum dibaca
     * oleh user ini (lihat AnnouncementNotification/AnnouncementService).
     * BEDA dari 4 jenis di atas - ini SUNGGUH tersimpan per-user di tabel
     * `notifications`, bukan dihitung ulang dari data lain tiap saat.
     *
     * $notification->id di sini adalah UUID asli baris notifications -
     * dipakai navbar.blade.php untuk menandai dibaca lewat
     * route('announcements.read', $id) saat diklik.
     */
    private function getAnnouncementNotifications(int $userId): array
    {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return [];
        }

        return $user->unreadNotifications()
            ->where('type', AnnouncementNotification::class)
            ->latest()
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn ($notification) => [
                'id'      => $notification->id,
                'type'    => 'announcement',
                'icon'    => 'bi-megaphone text-primary',
                'title'   => $notification->data['title'] ?? 'Pengumuman',
                'message' => $notification->data['message'] ?? '',
                'url'     => route('announcements.index'),
            ])->all();
    }

    /**
     * Daftar SEMUA jenis notifikasi (termasuk yang belum punya baris di
     * database sama sekali - lihat TYPE_LABELS sebagai sumber kebenaran
     * daftar jenis, bukan tabelnya), urut sesuai sort_order tersimpan,
     * untuk dirender di panel "Kelola Notifikasi Otomatis" halaman
     * Notifikasi (Super Admin).
     *
     * @return array<int, array{type: string, label: string, enabled: bool, sort_order: int}>
     */
    public function getManageableTypes(): array
    {
        $config = $this->getTypeConfig();

        $types = collect(self::TYPE_LABELS)->map(fn ($label, $type) => [
            'type'       => $type,
            'label'      => $label,
            'enabled'    => $config[$type]['enabled'] ?? true,
            'sort_order' => $config[$type]['sort_order'] ?? 999,
        ])->values();

        return $types->sortBy('sort_order')->values()->all();
    }

    /**
     * Simpan pengaturan baru dari form panel "Kelola Notifikasi Otomatis".
     *
     * @param  array<int, string>  $orderedTypes  Urutan type SETELAH diatur
     *                                            user (index array = urutan baru).
     * @param  array<int, string>  $enabledTypes  Daftar type yang dicentang aktif -
     *                                            type yang TIDAK ada di sini otomatis
     *                                            dianggap dinonaktifkan (checkbox HTML
     *                                            yang tidak dicentang tidak ikut terkirim).
     */
    public function updateTypeSettings(array $orderedTypes, array $enabledTypes): void
    {
        foreach ($orderedTypes as $index => $type) {
            NotificationTypeSetting::updateOrCreate(
                ['type' => $type],
                [
                    'sort_order' => $index,
                    'enabled'    => in_array($type, $enabledTypes, true),
                ]
            );
        }
    }

    /**
     * Sematkan/lepas-sematan (preferensi PRIBADI per user) 1 notifikasi
     * OTOMATIS (bukan Pengumuman - itu punya mekanisme sendiri di
     * AnnouncementService). $key adalah string stabil dari builder di
     * atas (contoh: "unpaid-42") - lihat migration create_notification_states_table
     * untuk keterbatasan yang disengaja soal kunci ini.
     */
    public function pinSystemNotification(int $userId, string $key): void
    {
        NotificationState::updateOrCreate(
            ['user_id' => $userId, 'notification_key' => $key],
            ['pinned_at' => now()]
        );
    }

    public function unpinSystemNotification(int $userId, string $key): void
    {
        NotificationState::updateOrCreate(
            ['user_id' => $userId, 'notification_key' => $key],
            ['pinned_at' => null]
        );
    }

    /**
     * Hapus 1 notifikasi OTOMATIS untuk SEMUA user (bukan cuma yang
     * menghapus) - lihat applyUserNotificationStates() untuk pengecekan
     * globalnya. $userId tetap dicatat (siapa yang menghapus), bukan
     * berarti hapusnya cuma berlaku untuk user itu.
     */
    public function deleteSystemNotification(int $userId, string $key): void
    {
        NotificationState::updateOrCreate(
            ['user_id' => $userId, 'notification_key' => $key],
            ['dismissed_at' => now()]
        );
    }
}

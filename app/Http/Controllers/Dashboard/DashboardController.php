<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    protected ActivityLogService $activityLogService;

    protected NotificationService $notificationService;

    public function __construct(
        DashboardService $dashboardService,
        ActivityLogService $activityLogService,
        NotificationService $notificationService
    ) {
        $this->dashboardService = $dashboardService;
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
    }

    /**
     * Dashboard permission-aware: tiap kartu hanya diambil datanya kalau
     * user yang login memang punya akses ke modul tersebut - baik supaya
     * tidak menampilkan data yang bukan urusannya, maupun supaya tidak
     * menjalankan query yang hasilnya toh tidak akan ditampilkan.
     *
     * Statistik global (getStatistics()) tetap 1 query batch di-cache seperti
     * semula karena angkanya sama untuk semua orang dan sangat ringan (COUNT).
     * Bagian View yang memutuskan kartu statistik mana yang benar-benar
     * dirender, berdasarkan hasPermission() masing-masing.
     */
    public function index(): View
    {
        $user = Auth::user();

        $statistics = $this->dashboardService->getStatistics();

        $myTasks = $user->hasPermission('tracking_progress', 'view')
            ? $this->dashboardService->getMyTasks($user)
            : collect();

        $upcomingProjects = $user->hasPermission('tracking_progress', 'view')
            ? $this->dashboardService->getUpcomingProjects()
            : collect();

        $recentSuratJalan = $user->hasPermission('surat_jalan', 'view')
            ? $this->dashboardService->getRecentSuratJalan()
            : collect();

        $borrowedUnitsCount = $user->hasPermission('borrowed_items', 'view')
            ? $this->dashboardService->getBorrowedUnitsCount()
            : null;

        $financeSummary = $user->hasPermission('finance', 'view')
            ? $this->dashboardService->getFinanceSummary()
            : null;

        // Employee hanya boleh melihat jejak aktivitasnya sendiri di Dashboard;
        // Super Admin & Admin tetap melihat aktivitas seluruh sistem seperti
        // semula (kondisi role ini sudah dipakai juga untuk tombol "Lihat Semua").
        $recentActivities = $this->activityLogService->getLatestActivities(
            4,
            $user->hasRole('super_admin', 'admin') ? null : $user->id
        );

        // Satu sumber: kartu "Perlu perhatian" dan feed memakai notifikasi yang sama dengan lonceng.
        $notifications = $this->notificationService->getActiveNotifications($user->id);
        $attention = $this->buildAttentionCards($user, $notifications);
        $latestNotifications = array_slice($notifications, 0, 5);

        return view(
            'dashboard.index',
            compact(
                'attention',
                'latestNotifications',
                'statistics',
                'myTasks',
                'upcomingProjects',
                'recentSuratJalan',
                'borrowedUnitsCount',
                'financeSummary',
                'recentActivities'
            )
        );
    }

    /**
     * Kartu KPI "Perlu perhatian" untuk Super Admin/Admin: jumlah notifikasi
     * otomatis per jenis plus tautan ke halaman penanganannya. Kartu hanya
     * muncul bila user punya akses ke halaman tujuannya.
     */
    private function buildAttentionCards(User $user, array $notifications): array
    {
        if (!$user->hasRole('super_admin', 'admin')) {
            return [];
        }

        $counts = array_count_values(array_column($notifications, 'type'));

        $definitions = [
            ['type' => 'approval_pending',   'label' => 'Approval menunggu', 'icon' => 'bi-patch-check', 'route' => 'approval.index', 'allowed' => true],
            ['type' => 'servis_jatuh_tempo', 'label' => 'Servis alat',       'icon' => 'bi-tools',       'route' => 'inventory.index', 'allowed' => $user->hasPermission('inventory', 'view')],
            ['type' => 'schedule_conflict',  'label' => 'Bentrok jadwal',    'icon' => 'bi-calendar2-x', 'route' => 'projects.index', 'allowed' => $user->hasPermission('tracking_progress', 'view')],
            ['type' => 'unpaid_deadline',    'label' => 'Belum lunas',       'icon' => 'bi-cash-coin',   'route' => 'projects.index', 'allowed' => $user->hasPermission('tracking_progress', 'view')],
        ];

        $cards = [];
        foreach ($definitions as $def) {
            if (!$def['allowed']) {
                continue;
            }

            $count = $counts[$def['type']] ?? 0;
            $cards[] = [
                'label' => $def['label'],
                'icon'  => $def['icon'],
                'route' => $def['route'],
                'count' => $count,
                // Daftar notifikasi dibatasi per jenis, jadi angka maksimum ditampilkan "10+".
                'text'  => $count >= NotificationService::MAX_PER_TYPE ? NotificationService::MAX_PER_TYPE . '+' : (string) $count,
            ];
        }

        return $cards;
    }
}

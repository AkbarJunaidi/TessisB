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
     * Dashboard permission-aware: data tiap bagian hanya diambil bila user
     * punya akses ke modulnya, jadi tidak ada query yang sia-sia.
     */
    public function index(): View
    {
        $user = Auth::user();

        $statistics = $this->dashboardService->getStatistics();

        $canProject = $user->hasPermission('tracking_progress', 'view');

        $myTasks = $canProject ? $this->dashboardService->getMyTasks($user) : collect();
        $week = $canProject ? $this->dashboardService->getWeekSchedule() : null;
        $pipeline = $canProject ? $this->dashboardService->getPipeline() : null;

        $recentSuratJalan = $user->hasPermission('surat_jalan', 'view')
            ? $this->dashboardService->getRecentSuratJalan()
            : collect();

        $equipment = $user->hasPermission('inventory', 'view')
            ? $this->dashboardService->getEquipmentStatus()
            : null;

        $financeSummary = $user->hasPermission('finance', 'view')
            ? $this->dashboardService->getFinanceSummary()
            : null;

        // Employee hanya melihat jejak aktivitasnya sendiri; Super Admin dan Admin melihat semua.
        $recentActivities = $this->activityLogService->getLatestActivities(
            5,
            $user->hasRole('super_admin', 'admin') ? null : $user->id
        );

        // Satu sumber dengan lonceng: hitungan "Perlu perhatian" dari semua kondisi aktif, feed hanya yang belum dibaca.
        $notifications = $this->notificationService->getActiveNotifications($user->id);
        $attention = $this->buildAttentionCards($user, $notifications);
        $latestNotifications = array_slice(array_values(array_filter($notifications, fn ($n) => empty($n['read']))), 0, 5);

        return view(
            'dashboard.index',
            compact(
                'attention',
                'latestNotifications',
                'statistics',
                'myTasks',
                'week',
                'pipeline',
                'equipment',
                'recentSuratJalan',
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
            ['type' => 'serial_pending',   'label' => 'Serial belum diisi', 'icon' => 'bi-upc-scan',   'route' => 'inventory.index', 'params' => ['serial_pending' => 1], 'allowed' => $user->hasPermission('inventory', 'edit')],
            ['type' => 'purchase_due',      'label' => 'Jatuh tempo',       'icon' => 'bi-calendar-x',  'route' => 'purchases.index', 'params' => ['due' => 1], 'allowed' => $user->hasPermission('purchase', 'pay') || $user->hasPermission('approval', 'decide')],
            ['type' => 'purchase_to_order',  'label' => 'Belum dipesan',     'icon' => 'bi-bag-check',   'route' => 'purchases.index', 'params' => ['status' => 'Disetujui'], 'allowed' => $user->hasPermission('purchase', 'view')],
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
                'params' => $def['params'] ?? [],
                'count' => $count,
                // Daftar notifikasi dibatasi per jenis, jadi angka maksimum ditampilkan "10+".
                'text'  => $count >= NotificationService::MAX_PER_TYPE ? NotificationService::MAX_PER_TYPE . '+' : (string) $count,
            ];
        }

        return $cards;
    }
}

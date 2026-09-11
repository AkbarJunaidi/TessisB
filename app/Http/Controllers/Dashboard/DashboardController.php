<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    protected ActivityLogService $activityLogService;

    public function __construct(
        DashboardService $dashboardService,
        ActivityLogService $activityLogService
    ) {
        $this->dashboardService = $dashboardService;
        $this->activityLogService = $activityLogService;
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

        return view(
            'dashboard.index',
            compact(
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
}

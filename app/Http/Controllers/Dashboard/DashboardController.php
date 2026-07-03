<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\DashboardService;
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

    public function index(): View
    {
        $statistics = $this->dashboardService->getStatistics();

        $recentActivities = $this->activityLogService
            ->getLatestActivities();

        return view(
            'dashboard.index',
            compact(
                'statistics',
                'recentActivities'
            )
        );
    }
}

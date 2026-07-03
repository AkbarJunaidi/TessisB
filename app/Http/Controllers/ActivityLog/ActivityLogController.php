<?php

namespace App\Http\Controllers\ActivityLog;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\ActivityLogFilterRequest;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Menampilkan halaman Activity Log.
     */
    public function index(ActivityLogFilterRequest $request): View
    {
        $filters = $request->validated();

        $logs = $this->activityLogService
            ->getFilteredLogs($filters);

        $users = $this->activityLogService
            ->getAllUsersForFilter();

        $modules = $this->activityLogService
            ->getModules();

        $actions = $this->activityLogService
            ->getActions();

        return view(
            'activity-logs.index',
            compact(
                'logs',
                'users',
                'modules',
                'actions',
                'filters'
            )
        );
    }
}

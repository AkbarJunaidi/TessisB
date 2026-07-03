<?php

namespace App\Services;

use App\Models\File;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class DashboardService
{
    /**
     * Mengambil seluruh statistik Dashboard.
     */
    public function getStatistics(): array
    {
        return [
            'total_inventory' => Inventory::count(),
            'total_project'   => Project::count(),
            'total_task'      => Task::count(),
            'total_files'     => File::count(),
            'total_user'      => User::count(),
        ];
    }
}

<?php

namespace App\Services\Dashboard;

use App\Models\File;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Lama cache statistik Dashboard (detik). Kartu statistik ini dibuka
     * berkali-kali oleh siapa pun yang login (halaman pertama setelah
     * login) - tanpa cache, tiap kunjungan menjalankan 5 query COUNT()
     * terpisah. Angka ringkasan seperti ini tidak perlu 100% real-time
     * detik-ke-detik, jadi cache pendek dipakai untuk mengurangi beban query
     * tanpa terasa basi bagi user.
     */
    private const CACHE_SECONDS = 60;

    /**
     * Mengambil seluruh statistik Dashboard.
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            'dashboard.statistics',
            self::CACHE_SECONDS,
            fn () => [
                'total_inventory' => Inventory::count(),
                'total_project'   => Project::count(),
                'total_task'      => Task::count(),
                'total_files'     => File::count(),
                'total_user'      => User::count(),
            ]
        );
    }
}

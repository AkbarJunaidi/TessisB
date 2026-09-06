<?php

namespace App\Services\ActivityLog;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    /**
     * Menyimpan log aktivitas baru.
     */
    public function log(
        ?int $userId,
        string $module,
        string $action
    ): void {
        try {
            ActivityLog::create([
                'user_id' => $userId,
                'module'  => $module,
                'action'  => $action,
            ]);
        } catch (\Throwable $e) {
            // Jangan menghentikan proses utama apabila Activity Log gagal disimpan
            Log::error('Activity Log Error : ' . $e->getMessage());
        }
    }

    /**
     * Mengambil daftar Activity Log berdasarkan filter.
     */
    public function getFilteredLogs(array $filters): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('user');

        // 1. Filter Module (Abaikan jika bernilai 'all' atau kosong)
        if (!empty($filters['module'])) {
            $moduleSearch = trim($filters['module']);
            $lowerModule = strtolower($moduleSearch);

            if (!in_array($lowerModule, ['all', 'all_module', 'all modules', 'semua'])) {
                $query->where(function ($q) use ($moduleSearch, $lowerModule) {
                    $q->where('module', 'LIKE', '%' . $moduleSearch . '%')
                      ->orWhereRaw('LOWER(module) = ?', [$lowerModule]);
                });
            }
        }

        // 2. Filter User
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 3. Filter Action (Abaikan jika bernilai 'all' atau kosong)
        if (!empty($filters['action'])) {
            $actionSearch = trim($filters['action']);
            $lowerAction = strtolower($actionSearch);

            if (!in_array($lowerAction, ['all', 'all_action', 'all actions', 'semua'])) {
                $query->where(function ($q) use ($actionSearch, $lowerAction) {
                    $q->where('action', 'LIKE', '%' . $actionSearch . '%')
                      ->orWhereRaw('LOWER(action) = ?', [$lowerAction]);
                });
            }
        }

        // 4. Filter Global Search Keyword
        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);

            $query->where(function ($q) use ($keyword) {
                $q->where('module', 'like', "%{$keyword}%")
                  ->orWhere('action', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhereHas('user', function ($userQuery) use ($keyword) {
                      $userQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                  });
            });
        }

        // 5. Filter Tanggal Awal
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // 6. Filter Tanggal Akhir
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();
    }

    /**
     * Menghapus seluruh Activity Log dalam rentang tanggal tertentu
     * (inklusif, berdasarkan tanggal `created_at`). Operasi permanen -
     * tidak melalui Trash. Hanya boleh dipanggil setelah otorisasi Super
     * Admin diverifikasi di layer Controller.
     *
     * Aksi ini sengaja tetap dicatat ke Activity Log (dengan module
     * "Activity Log") setelah proses hapus selesai, supaya jejak
     * penghapusan massal ini sendiri tetap ada di audit trail - mengikuti
     * pola yang sama dengan TrashService::forceDelete().
     *
     * @return int Jumlah baris yang berhasil dihapus.
     */
    public function deleteByDateRange(string $dateFrom, string $dateTo): int
    {
        $deletedCount = ActivityLog::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->delete();

        $this->log(
            Auth::id(),
            'Activity Log',
            "Delete Activity Log Range ({$dateFrom} s/d {$dateTo}) - {$deletedCount} data terhapus"
        );

        return $deletedCount;
    }

    /**
     * Mengambil Activity Log terbaru untuk Dashboard.
     */
    public function getLatestActivities(int $limit = 4): Collection
    {
        return ActivityLog::with('user')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Mengambil seluruh user untuk dropdown filter.
     */
    public function getAllUsersForFilter(): Collection
    {
        return User::orderBy('name')
            ->get([
                'id',
                'name',
            ]);
    }

    /**
     * Mengambil daftar modul Activity Log (Gabungan DB Unik & Default).
     */
    public function getModules(): array
    {
        // Mengambil daftar unik dari DB (misal: 'App\Models\Inventory')
        $rawModules = ActivityLog::query()
            ->whereNotNull('module')
            ->distinct()
            ->pluck('module')
            ->toArray();

        $modules = [];

        // Masukkan daftar default modul
        $defaultModules = [
            'Authentication'    => 'Authentication',
            'Inventory'         => 'Inventory',
            'Tracking Progress' => 'Tracking Progress',
            'Integrasi Data'    => 'Integrasi Data',
            'User Management'   => 'User Management',
        ];

        foreach ($defaultModules as $key => $val) {
            $modules[$key] = $val;
        }

        // Gabungkan dengan modul unik yang ada di DB
        foreach ($rawModules as $module) {
            $shortName = class_basename($module);
            $modules[$module] = $shortName;
        }

        return $modules;
    }

    /**
     * Mengambil daftar aktivitas berdasarkan modul.
     */
    public function getActions(): array
    {
        return [
            'Authentication' => [
                'Login',
                'Logout',
            ],

            'Inventory' => [
                'Create Inventory',
                'Update Inventory',
                'Delete Inventory',
                'Created',
                'Updated',
                'Deleted',
                'created',
                'updated',
                'deleted',
            ],

            'Tracking Progress' => [
                'Create Project',
                'Update Project',
                'Delete Project',
                'Create Task',
                'Update Task',
                'Update Task Status',
                'Delete Task',
            ],

            'Integrasi Data' => [
                'Upload File',
                'Download File',
                'Rename File',
                'Move File',
                'Delete File',
                'Create Folder',
                'Rename Folder',
                'Move Folder',
                'Delete Folder',
            ],

            'User Management' => [
                'Create User',
                'Update User',
                'Change Password',
                'Change Role',
                'Delete User',
            ],
        ];
    }
}

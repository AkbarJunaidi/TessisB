<?php

namespace App\Services\ActivityLog;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    /**
     * Menyimpan Activity Log.
     *
     * Parameter:
     * - userId : ID user yang melakukan aksi
     * - module : Nama modul
     * - action : Nama aktivitas
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
        $query = ActivityLog::query()
            ->with('user');

        // Filter Module
        $query->when(
            $filters['module'] ?? null,
            fn ($query, $module) => $query->where('module', $module)
        );

        // Filter User
        $query->when(
            $filters['user_id'] ?? null,
            fn ($query, $userId) => $query->where('user_id', $userId)
        );

        // Filter Action
        $query->when(
            $filters['action'] ?? null,
            fn ($query, $action) => $query->where('action', $action)
        );

        // Filter Search
        $query->when(
            $filters['search'] ?? null,
            function ($query, $keyword) {

                $query->where(function ($query) use ($keyword) {

                    $query->where('module', 'like', "%{$keyword}%")
                        ->orWhere('action', 'like', "%{$keyword}%")
                        ->orWhereHas('user', function ($query) use ($keyword) {

                            $query->where('name', 'like', "%{$keyword}%");

                        });

                });

            }
        );

        // Filter Tanggal Awal
        $query->when(
            $filters['date_from'] ?? null,
            fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
        );

        // Filter Tanggal Akhir
        $query->when(
            $filters['date_to'] ?? null,
            fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
        );

        return $query
            ->latest()
            ->paginate(
                $filters['per_page'] ?? 10
            )
            ->withQueryString();
    }

    /**
     * Mengambil Activity Log terbaru
     * untuk Dashboard.
     */
    public function getLatestActivities(int $limit = 4): Collection
    {
        return ActivityLog::with('user')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Mengambil seluruh user
     * untuk dropdown filter.
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
     * Mengambil daftar modul Activity Log.
     */
    public function getModules(): array
    {
        return [
            'Authentication',
            'Inventory',
            'Tracking Progress',
            'Integrasi Data',
            'User Management',
        ];
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

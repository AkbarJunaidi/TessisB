<?php

namespace App\Services;

use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\DataIntegration\FolderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    protected ActivityLogService $activityLogService;
    protected FolderService $folderService;

    public function __construct(ActivityLogService $activityLogService, FolderService $folderService)
    {
        $this->activityLogService = $activityLogService;
        $this->folderService = $folderService;
    }

//  Mengambil data project dengan pagination + filter (search, status, pic, bulan, tanggal).
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Project::with('creator', 'suratJalans');

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('pic', 'like', "%{$keyword}%")
                  ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'Semua Status') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['pic']) && $filters['pic'] !== 'Semua PIC') {
            $query->where('pic', $filters['pic']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('event_date', $filters['month']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('event_date', $filters['date']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

// Statistik ringkas untuk dashboard Project Management.
    public function getStats(): array
    {
        return [
            'total'      => Project::count(),
            'today'      => Project::whereDate('event_date', now()->toDateString())->count(),
            'in_progress' => Project::where('status', 'In Progress')->count(),
            'done'       => Project::where('status', 'Done')->count(),
        ];
    }

// Data untuk kalender project pada bulan & tahun tertentu (jumlah project per tanggal).
    public function getCalendarData(int $month, int $year): array
    {
        return Project::whereMonth('event_date', $month)
            ->whereYear('event_date', $year)
            ->whereNotNull('event_date')
            ->get()
            ->groupBy(fn ($project) => $project->event_date->format('Y-m-d'))
            ->map->count()
            ->toArray();
    }

// Daftar PIC unik untuk dropdown filter.
    public function getDistinctPics(): array
    {
        return Project::whereNotNull('pic')->distinct()->orderBy('pic')->pluck('pic')->toArray();
    }

// Menambahkan project baru.
    public function createProject(array $data): Project
    {
        $project = Project::create([
            'name'                        => $data['name'],
            'client'                      => $data['client'],
            'pic'                         => $data['pic'],
            'category'                    => $data['category'],
            'description'                 => $data['description'] ?? null,
            'deadline'                    => $data['deadline'],
            'event_date'                  => $data['event_date'],
            'event_time_start'            => $data['event_time_start'],
            'event_time_end'              => $data['event_time_end'] ?? null,
            'location'                    => $data['location'],
            'address'                     => $data['address'],
            'estimated_duration_minutes'  => $data['estimated_duration_minutes'],
            'priority'                    => $data['priority'],
            'created_by'                  => Auth::id(),
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Create Project'
        );

        // Document Center: buat folder khusus project secara otomatis (PRD Bagian 9).
        $this->folderService->getOrCreateProjectFolder($project);

        return $project;
    }

// Memperbarui data project yang sudah ada.
    public function updateProject(Project $project, array $data): Project
    {
        $project->update([
            'name'                        => $data['name'],
            'client'                      => $data['client'],
            'pic'                         => $data['pic'],
            'category'                    => $data['category'],
            'description'                 => $data['description'] ?? null,
            'deadline'                    => $data['deadline'],
            'event_date'                  => $data['event_date'],
            'event_time_start'            => $data['event_time_start'],
            'event_time_end'              => $data['event_time_end'] ?? null,
            'location'                    => $data['location'],
            'address'                     => $data['address'],
            'estimated_duration_minutes'  => $data['estimated_duration_minutes'],
            'priority'                    => $data['priority'],
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Update Project'
        );

        return $project;
    }

//  Menghapus project (Soft Delete).
    public function deleteProject(Project $project): bool
    {
        $deleted = $project->delete();

        if ($deleted) {
            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                'Delete Project'
            );
        }

        return $deleted;
    }
}

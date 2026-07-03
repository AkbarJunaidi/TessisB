<?php

namespace App\Services;

use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

//  Mengambil seluruh data project dengan pagination.
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Project::with('creator')
            ->latest()
            ->paginate($perPage);
    }

// Menambahkan project baru.
    public function createProject(array $data): Project
    {
        $project = Project::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'deadline'    => $data['deadline'],
            'created_by'  => Auth::id(),
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Create Project'
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

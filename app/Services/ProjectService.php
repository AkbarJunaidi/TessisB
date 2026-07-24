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

//  Menambahkan list/kolom board baru pada project.
    public function addBoardList(Project $project, string $label): array
    {
        $label = trim($label);
        $lists = $project->getBoardLists();

        $alreadyExists = collect($lists)->contains(
            fn ($list) => strcasecmp($list['label'], $label) === 0
        );

        if ($alreadyExists) {
            throw new \InvalidArgumentException(
                "List \"{$label}\" sudah ada di board ini."
            );
        }

        $nextColor = Project::LIST_COLOR_PALETTE[
            count($lists) % count(Project::LIST_COLOR_PALETTE)
        ];

        $lists[] = [
            'label' => $label,
            'color' => $nextColor,
        ];

        $project->update(['board_lists' => $lists]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            "Add List: {$label}"
        );

        return $lists;
    }
}

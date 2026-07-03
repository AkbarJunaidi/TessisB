<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class TaskService
{

    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }


    //  * Mengambil seluruh task berdasarkan project.
    public function getTasksByProject(Project $project)
    {
        return Task::with([
            'assignee',
            'comments.user'
        ])
        ->where('project_id', $project->id)
        ->get()
        ->groupBy('status');
    }


    //  * Menambahkan task baru.
    public function createTask(array $data): Task
    {
        $task = Task::create([
            'project_id'  => $data['project_id'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'Todo',
            'priority'    => $data['priority'] ?? 'Medium',
            'deadline'    => $data['deadline'],
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Create Task'
        );

        return $task;
    }

    //  * Mengambil detail task berdasarkan ID.
    public function findTaskById(int $id): Task
    {
        return Task::with([
            'project',
            'assignee',
            'comments.user'
        ])->findOrFail($id);
    }

    //  * Memperbarui data task.
    public function updateTask(Task $task, array $data): Task
    {
        $task->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'priority'    => $data['priority'] ?? 'Medium',
            'deadline'    => $data['deadline'],
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Update Task'
        );

        return $task->fresh([
            'project',
            'assignee',
            'comments.user'
        ]);
    }

    //  * Mengubah status task.
    public function updateTaskStatus(Task $task, string $newStatus): bool
    {
        $updated = $task->update([
            'status' => $newStatus,
        ]);

        if ($updated) {
            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                'Update Task Status'
            );
        }

        return $updated;
    }

    //  * Menghapus task (Soft Delete).
    public function deleteTask(Task $task): bool
    {
        $deleted = $task->delete();

        if ($deleted) {
            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                'Delete Task'
            );
        }

        return $deleted;
    }
}

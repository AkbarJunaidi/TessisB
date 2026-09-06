<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ProjectNoteService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Menyimpan catatan baru pada sebuah project.
     *
     * @throws Exception
     */
    public function storeNote(array $data): ProjectNote
    {
        return DB::transaction(function () use ($data) {
            $note = ProjectNote::create([
                'project_id' => $data['project_id'],
                'user_id'    => Auth::id(),
                'note'       => $data['note'],
            ]);

            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                'Menambahkan catatan pada project #' . $data['project_id']
            );

            return $note;
        });
    }

    /**
     * Menghapus catatan. Hanya pembuat catatan atau Super Admin yang boleh menghapus.
     */
    public function deleteNote(ProjectNote $note): bool
    {
        $user = Auth::user();

        if ($note->user_id !== $user->id && !$user->isSuperAdmin()) {
            throw new Exception('Anda tidak memiliki izin untuk menghapus catatan ini.');
        }

        return $note->delete();
    }

    /**
     * Mengambil seluruh catatan sebuah project (terbaru di atas).
     */
    public function getNotesForProject(Project $project)
    {
        return $project->notes()->with('user')->latest()->get();
    }
}

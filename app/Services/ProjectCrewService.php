<?php

namespace App\Services;

use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class ProjectCrewService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Sinkronkan seluruh crew project (replace all) berdasarkan input form.
     * Menggunakan sync() Eloquent, bukan insert manual, agar pivot lama yang
     * tidak lagi dikirim otomatis terhapus tanpa query tambahan.
     *
     * @param  array<int, array{user_id:int, role_label:string}>  $crewData
     */
    public function syncCrew(Project $project, array $crewData): Project
    {
        $syncPayload = collect($crewData)
            ->mapWithKeys(fn (array $row) => [
                $row['user_id'] => ['role_label' => $row['role_label']],
            ])
            ->all();

        $project->crews()->sync($syncPayload);

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Update Crew Project'
        );

        return $project->load('crews');
    }
}

<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\ProjectCrew;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectCrewService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Sinkronkan seluruh crew project (replace all) berdasarkan input form.
     * Crew berupa nama teks bebas (tidak terikat akun user) - baris lama
     * dihapus lalu diganti dengan baris baru dalam satu transaksi, supaya
     * tidak ada state "setengah tersimpan" kalau terjadi error di tengah jalan.
     *
     * @param  array<int, array{name:string, role_label:string}>  $crewData
     */
    public function syncCrew(Project $project, array $crewData): Project
    {
        DB::transaction(function () use ($project, $crewData) {
            $project->crews()->delete();

            $rows = collect($crewData)
                ->filter(fn (array $row) => filled($row['name'] ?? null) && filled($row['role_label'] ?? null))
                ->map(fn (array $row) => [
                    'project_id' => $project->id,
                    'name'       => trim($row['name']),
                    'role_label' => trim($row['role_label']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if (!empty($rows)) {
                ProjectCrew::insert($rows);
            }
        });

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            'Update Crew Project'
        );

        return $project->load('crews');
    }
}

<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectFinanceService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Sinkronkan seluruh item Pendapatan & Pengeluaran project (replace all)
     * berdasarkan input form - mengikuti pola yang sama dengan
     * ProjectCrewService::syncCrew() (hapus lama, insert baru dalam 1 transaction).
     *
     * @param  array<int, array{amount: float, description: ?string}>  $incomes
     * @param  array<int, array{amount: float, description: ?string}>  $expenses
     */
    public function syncFinanceItems(Project $project, array $incomes, array $expenses): Project
    {
        DB::transaction(function () use ($project, $incomes, $expenses) {
            $project->financeItems()->delete();

            $rows = collect($incomes)
                ->map(fn (array $row) => [
                    'project_id'  => $project->id,
                    'type'        => 'income',
                    'amount'      => $row['amount'],
                    'description' => $row['description'] ?? null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])
                ->concat(
                    collect($expenses)->map(fn (array $row) => [
                        'project_id'  => $project->id,
                        'type'        => 'expense',
                        'amount'      => $row['amount'],
                        'description' => $row['description'] ?? null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ])
                );

            if ($rows->isNotEmpty()) {
                \App\Models\ProjectFinanceItem::insert($rows->all());
            }

            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                "Memperbarui data keuangan project \"{$project->name}\""
            );
        });

        return $project->load('financeItems');
    }
}

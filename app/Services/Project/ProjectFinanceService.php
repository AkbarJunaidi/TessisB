<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use App\Support\FinanceLock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectFinanceService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Sinkronkan item Pendapatan & Pengeluaran project dari form Data Keuangan. Baris ber-id diperbarui
     * (kolom lain seperti tanggal, kategori, dan sumber tetap), baris baru ditambah, dan hanya baris yang
     * dimuat form lalu dihapus pengguna yang dihapus. Baris yang masuk setelah form dimuat tidak disentuh.
     * Sekaligus memperbarui Estimasi Pendapatan (field tampilan saja, TIDAK ikut dihitung sebagai Pendapatan asli).
     *
     * @param  array<int, array{id?: int, amount: float, description: ?string}>  $incomes
     * @param  array<int, array{id?: int, amount: float, description: ?string}>  $expenses
     * @param  array<int, int>  $loadedIds
     */
    public function syncFinanceItems(Project $project, array $incomes, array $expenses, ?float $estimatedValue = null, array $loadedIds = [], ?float $budget = null): Project
    {
        DB::transaction(function () use ($project, $incomes, $expenses, $estimatedValue, $loadedIds, $budget) {
            $project->update(['estimated_value' => $estimatedValue, 'budget' => $budget]);

            $existing = $project->financeItems()->get()->keyBy('id');
            $kept = [];

            foreach (['income' => $incomes, 'expense' => $expenses] as $type => $rows) {
                foreach ($rows as $row) {
                    $item = isset($row['id']) ? $existing->get((int) $row['id']) : null;

                    if ($item && $item->type === $type) {
                        $changed = (float) $item->amount !== (float) $row['amount'] || (string) $item->description !== (string) ($row['description'] ?? '');

                        // Baris di periode yang sudah ditutup tidak boleh berubah (tanpa perubahan tetap lolos).
                        if ($changed) {
                            FinanceLock::assertOpen($item->tanggal);
                        }

                        $item->update(['amount' => $row['amount'], 'description' => $row['description'] ?? null]);
                    } else {
                        FinanceLock::assertOpen(now());

                        $item = $project->financeItems()->create([
                            'type'        => $type,
                            'amount'      => $row['amount'],
                            'description' => $row['description'] ?? null,
                            'created_by'  => Auth::id(),
                        ]);
                    }

                    $kept[] = $item->id;
                }
            }

            $removed = $project->financeItems()->whereIn('id', array_diff($loadedIds, $kept))->get();
            $removed->each(fn ($item) => FinanceLock::assertOpen($item->tanggal));
            $project->financeItems()->whereIn('id', $removed->pluck('id'))->delete();

            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                "Memperbarui data keuangan project \"{$project->name}\""
            );
        });

        return $project->load('financeItems');
    }
}

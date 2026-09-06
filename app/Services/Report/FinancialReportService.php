<?php

namespace App\Services\Report;

use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;

class FinancialReportService
{
    /**
     * Query dasar: seluruh Project pada bulan & tahun tertentu, berdasarkan
     * Tanggal Acara (event_date) - mengikuti periode yang sama dengan
     * Kalender Project di dashboard. Method ini query builder (belum di-get()),
     * supaya bisa dipakai ulang untuk kebutuhan lain (exists(), count(), dst)
     * tanpa duplikasi kondisi WHERE.
     */
    protected function monthlyProjectsQuery(int $month, int $year): Builder
    {
        return Project::whereYear('event_date', $year)
            ->whereMonth('event_date', $month);
    }

    /**
     * Memastikan seluruh Project pada bulan tersebut sudah lengkap data
     * keuangannya (minimal punya 1 item Pendapatan/Pengeluaran). Melempar
     * Exception kalau belum, dengan pesan yang sama persis dengan spec.
     *
     * @throws Exception
     */
    public function validateMonthIsComplete(int $month, int $year): void
    {
        $hasIncomplete = $this->monthlyProjectsQuery($month, $year)
            ->doesntHave('financeItems')
            ->exists();

        if ($hasIncomplete) {
            throw new Exception('Masih terdapat Project pada bulan ini yang belum melengkapi data keuangan.');
        }
    }

    /**
     * Ringkasan keuangan 1 bulan: daftar project beserta totalnya.
     * Ini logic INTI yang reusable - laporan lain (Laba Rugi, Rekap Project)
     * nantinya tinggal pakai method ini juga, tidak perlu tulis ulang query
     * atau kalkulasi total.
     */
    public function getMonthlySummary(int $month, int $year): array
    {
        $projects = $this->monthlyProjectsQuery($month, $year)
            ->with('financeItems')
            ->orderBy('event_date')
            ->get();

        $totalRevenue = (float) $projects->sum(fn (Project $p) => $p->total_income);
        $totalExpense = (float) $projects->sum(fn (Project $p) => $p->total_expense);

        // Rekap per-baris (bukan per-project) - dipakai untuk tabel "Rekap Pendapatan"
        // dan "Rekap Pengeluaran", diurutkan dari yang paling awal diinput.
        $incomeItems = $projects
            ->flatMap(fn (Project $p) => $p->financeItems->where('type', 'income')
                ->map(function ($item) use ($p) {
                    $item->project_name = $p->name;
                    return $item;
                }))
            ->sortBy('created_at')
            ->values();

        $expenseItems = $projects
            ->flatMap(fn (Project $p) => $p->financeItems->where('type', 'expense')
                ->map(function ($item) use ($p) {
                    $item->project_name = $p->name;
                    return $item;
                }))
            ->sortBy('created_at')
            ->values();

        return [
            'projects' => $projects,
            'total_project' => $projects->count(),
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'total_profit' => $totalRevenue - $totalExpense,
            'income_items' => $incomeItems,
            'expense_items' => $expenseItems,
        ];
    }

    /**
     * Orkestrasi penuh: validasi -> hitung ringkasan -> render Blade PDF -> DOMPDF.
     * Controller cukup panggil method ini saja (tidak menyentuh HTML/Blade sama sekali).
     *
     * @throws Exception jika ada project yang belum lengkap data keuangannya
     */
    public function generateMonthlyPdf(int $month, int $year)
    {
        $this->validateMonthIsComplete($month, $year);

        $summary = $this->getMonthlySummary($month, $year);

        $pdf = Pdf::loadView('report.finance.monthly', [
            'summary'    => $summary,
            'periodDate' => \Carbon\Carbon::createFromDate($year, $month, 1),
            'exportDate' => now(),
            'printedBy'  => Auth::user()->name ?? 'Admin',
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}

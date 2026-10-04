<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tab "Ringkasan" halaman Keuangan: laba/rugi, tren 12 bulan, komposisi pengeluaran, peringkat. Hanya baca (finance.view).
 */
class FinanceSummaryController extends Controller
{
    public function __construct(
        protected FinanceSummaryService $summaryService
    ) {}

    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data keuangan.'
        );

        $period = $this->summaryService->resolvePeriod(
            $request->query('period'),
            $request->query('from'),
            $request->query('to')
        );

        ['from' => $from, 'to' => $to] = $period;

        return view('finance.summary.index', [
            'period'     => $period,
            'presets'    => FinanceSummaryService::PRESETS,
            'overview'   => $this->summaryService->overview($from, $to),
            'trend'      => $this->summaryService->trend($to),
            'categories' => $this->summaryService->expenseByCategory($from, $to),
            'projects'   => $this->summaryService->projectProfits($from, $to),
            'clients'    => $this->summaryService->topClients($from, $to),
            'payables'   => $this->summaryService->payables(),
            'receivables' => $this->summaryService->receivables(),
            'overBudget' => $this->summaryService->overBudget(),
            'kwitansi'   => $this->summaryService->kwitansiTotal($from, $to),
        ]);
    }
}

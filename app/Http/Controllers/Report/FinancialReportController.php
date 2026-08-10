<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Reports\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialReportService
    ) {}

    /**
     * Export Laporan Keuangan Bulanan (PDF). Mengikuti bulan/tahun yang
     * sedang dipilih di Kalender Project. Kalau ada project yang belum
     * lengkap data keuangannya, kembalikan error JSON (422) - front-end
     * (fetch AJAX) yang menampilkan pesannya, PDF tidak jadi dibuat.
     */
    public function exportMonthly(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'between:2020,2100'],
        ]);

        try {
            $pdf = $this->financialReportService->generateMonthlyPdf(
                (int) $validated['month'],
                (int) $validated['year']
            );
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $periode = \Carbon\Carbon::createFromDate((int) $validated['year'], (int) $validated['month'], 1);
        $filename = 'Laporan-Keuangan-' . $periode->translatedFormat('F-Y') . '.pdf';

        return $pdf->download($filename);
    }
}

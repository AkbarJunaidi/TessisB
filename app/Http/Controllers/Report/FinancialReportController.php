<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
     *
     * Default-nya hanya Super Admin (lihat config/permissions.php), tapi bisa
     * diaktifkan untuk role lain lewat Permission Override di Edit User.
     */
    public function exportMonthly(Request $request): Response|JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'export_report'),
            403,
            'Anda tidak memiliki hak akses untuk export laporan keuangan.'
        );

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

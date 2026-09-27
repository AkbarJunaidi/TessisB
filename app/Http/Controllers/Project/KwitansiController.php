<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\KwitansiRequest;
use App\Http\Requests\Project\KwitansiVoidRequest;
use App\Models\Kwitansi;
use App\Models\Project;
use App\Services\Project\KwitansiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class KwitansiController extends Controller
{
    public function __construct(protected KwitansiService $kwitansiService)
    {
    }

    /**
     * Halaman "Keuangan" - ringkasan tagihan/pembayaran SEMUA project,
     * berdiri sendiri (entry point ke-2 selain tab Kwitansi di Project
     * Detail - pola yang sama dengan halaman Barang Pinjaman).
     */
    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data keuangan.'
        );

        $search = trim((string) $request->query('search', ''));
        $canCreate = Auth::user()->hasPermission('finance', 'create_kwitansi');

        $projects = $this->kwitansiService->getProjectPaymentSummaries($search ?: null);

        return view('kwitansi.index', compact('projects', 'search', 'canCreate'));
    }

    /**
     * Halaman "Detail Keuangan" 1 project - dibuka dari tombol "Detail" di
     * halaman Keuangan. Isinya sama dengan tab Kwitansi di Project Detail
     * (partial yang sama, project.partials.kwitansi-content) TAPI berdiri
     * sendiri, tidak perlu masuk ke halaman Project penuh.
     */
    public function showProject(Project $project): View
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data keuangan.'
        );

        $project->load(['financeItems', 'kwitansis' => fn ($q) => $q->latest()]);

        return view('kwitansi.project-detail', compact('project'));
    }

    /**
     * print_after=1 (dicentang lewat modal "Cetak Kwitansi" di baris
     * Pendapatan) -> langsung ke preview PDF, bukan balik ke halaman asal.
     */
    public function store(KwitansiRequest $request, Project $project): RedirectResponse
    {
        $kwitansi = $this->kwitansiService->create($project, $request->validated());

        if ($request->boolean('print_after')) {
            return redirect()->route('kwitansi.preview', $kwitansi);
        }

        return redirect()
            ->back()
            ->with('success', 'Kwitansi berhasil dibuat.');
    }

    public function preview(Kwitansi $kwitansi)
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat kwitansi.'
        );

        return $this->kwitansiService->generatePdf($kwitansi, stream: true);
    }

    public function download(Kwitansi $kwitansi)
    {
        abort_unless(
            Auth::user()?->hasPermission('finance', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk mengunduh kwitansi.'
        );

        return $this->kwitansiService->generatePdf($kwitansi, stream: false);
    }

    public function requestVoid(KwitansiVoidRequest $request, Kwitansi $kwitansi): RedirectResponse
    {
        try {
            $this->kwitansiService->requestVoid($kwitansi, $request->validated()['reason']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Permintaan pembatalan diajukan, menunggu approval Super Admin.');
    }
}

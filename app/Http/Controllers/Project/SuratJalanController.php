<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ReturnBarangRequest;
use App\Http\Requests\Project\SuratJalanRequest;
use App\Models\Inventory;
use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\SuratJalanItem;
use App\Services\Project\SuratJalanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuratJalanController extends Controller
{
    public function __construct(
        protected SuratJalanService $suratJalanService
    ) {}

    /**
     * Menampilkan form pembuatan Surat Jalan untuk sebuah project.
     */
    public function create(Project $project): View
    {
        $inventories = Inventory::withAvailability()->orderBy('name')->get();

        return view('surat-jalan.create', compact('project', 'inventories'));
    }

    /**
     * Menyimpan Surat Jalan baru: validasi stok, kurangi stok, generate PDF.
     */
    public function store(SuratJalanRequest $request, Project $project): RedirectResponse
    {
        try {
            $suratJalan = $this->suratJalanService->createSuratJalan(
                $project,
                $request->validated()
            );

            // Generate PDF sekaligus saat Surat Jalan dibuat (sesuai alur PRD).
            $this->suratJalanService->generatePdf($suratJalan, stream: false);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Surat Jalan {$suratJalan->nomor} berhasil dibuat dan stok inventory telah diperbarui.");
    }

    /**
     * Menampilkan detail Surat Jalan (untuk kelola Kembalikan Barang).
     */
    public function show(SuratJalan $suratJalan): View
    {
        $suratJalan->load('items.inventory', 'project');

        return view('surat-jalan.show', compact('suratJalan'));
    }

    /**
     * Preview PDF (tampil di browser).
     */
    public function preview(SuratJalan $suratJalan)
    {
        return $this->suratJalanService->generatePdf($suratJalan, stream: true);
    }

    /**
     * Download PDF (attachment).
     */
    public function download(SuratJalan $suratJalan)
    {
        return $this->suratJalanService->generatePdf($suratJalan, stream: false);
    }

    /**
     * Mengembalikan barang (partial return) untuk satu baris item Surat Jalan.
     */
    public function returnItem(ReturnBarangRequest $request, SuratJalanItem $item): RedirectResponse
    {
        try {
            $this->suratJalanService->returnItem($item, (int) $request->validated()['qty']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Barang berhasil dikembalikan ke inventory.');
    }
}

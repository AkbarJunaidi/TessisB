<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRequest;
use App\Models\Inventory;
use App\Models\ReportExport;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Service Inventory.
     */
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Menampilkan daftar inventory dengan pencarian nama barang & filter status dropdown.
     */
    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data inventory.'
        );

        $filters = $request->only(['search', 'status']);
        $inventories = $this->inventoryService->getAllPaginated($filters, 10);

        return view('inventory.index', compact('inventories'));
    }

    /**
     * Menampilkan halaman tambah inventory.
     */
    public function create(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'create'),
            403,
            'Anda tidak memiliki hak akses untuk menambah data inventory.'
        );

        return view('inventory.create');
    }

    /**
     * Menyimpan inventory baru.
     */
    public function store(InventoryRequest $request): RedirectResponse
    {
        $this->inventoryService->createInventory(
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Data inventory baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail inventory beserta informasi tambahan (dynamic attributes).
     */
    public function show(Inventory $inventory): View
    {
        $inventory->load('attributes', 'units.suratJalanItem.suratJalan');

        return view('inventory.show', compact('inventory'));
    }

    /**
     * Menampilkan halaman edit inventory.
     */
    public function edit(Inventory $inventory): View
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'edit'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data inventory.'
        );

        $inventory->load('attributes', 'units.suratJalanItem.suratJalan');

        return view('inventory.edit', compact('inventory'));
    }

    /**
     * Memperbarui data inventory.
     */
    public function update(
        InventoryRequest $request,
        Inventory $inventory
    ): RedirectResponse {

        try {
            $this->inventoryService->updateInventory(
                $inventory,
                $request->validated(),
                $request->file('image')
            );
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventory.show', $inventory)
            ->with('success', 'Data inventory berhasil diperbarui.');
    }

    /**
     * [AJAX] Mengubah status kondisi 1 unit fisik (Tersedia/Rusak/Perbaikan/Hilang)
     * tanpa reload halaman - dipanggil per-baris dari tabel Kelola Unit Fisik.
     */
    public function updateUnitStatus(Request $request, Inventory $inventory, \App\Models\InventoryUnit $unit): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:Tersedia,Rusak,Perbaikan,Hilang'],
        ]);

        if ($unit->inventory_id !== $inventory->id) {
            return response()->json(['message' => 'Unit tidak ditemukan pada barang ini.'], 404);
        }

        try {
            $updated = $this->inventoryService->updateUnitStatus($unit, $request->input('status'));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Status Unit #{$updated->unit_number} berhasil diperbarui.",
            'unit'    => $updated,
        ]);
    }

    /**
     * Menghapus inventory.
     */
    public function destroy(Inventory $inventory): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'delete'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus data inventory.'
        );

        $this->inventoryService->deleteInventory($inventory);

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Data inventory berhasil dihapus.');
    }

    /**
     * Menampilkan pratinjau QR Code Label bawaan.
     */
    public function previewQr(Inventory $inventory)
    {
        // Langsung arahkan ke logic renderer PDF dengan mode stream inline
        return $this->inventoryService->generateLabelPdf($inventory, $stream = true);
    }

    /**
     * Menampilkan pratinjau (inline stream) dokumen Laporan Inventaris A4
     * untuk 1 barang di browser tanpa mengunduhnya langsung.
     */
    public function previewPdf(Inventory $inventory)
    {
        return $this->inventoryService->generateSingleReport($inventory, $stream = true);
    }

    /**
     * Memicu proses unduhan langsung (forced attachment download)
     * file PDF Laporan Inventaris A4 untuk 1 barang spesifik.
     */
    public function downloadPdf(Inventory $inventory)
    {
        return $this->inventoryService->generateSingleReport($inventory, $stream = false);
    }

    /**
     * Menampilkan pratinjau (inline stream) dokumen gabungan Laporan Seluruh Inventaris
     * dalam format A4 Portrait (1 barang per halaman) di browser.
     */
    public function previewAllPdf()
    {
        return $this->inventoryService->generateAllReport($stream = true);
    }

    /**
     * [AJAX] Memulai proses pembuatan Laporan Massal Seluruh Inventaris.
     * Dipanggil sekali dari JavaScript saat tombol diklik - hanya mencatat
     * permintaan (cepat), pemrosesan sesungguhnya terjadi lewat panggilan
     * berulang ke processAllReportBatch().
     */
    public function startAllReportExport(): \Illuminate\Http\JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data inventory.'
        );

        $reportExport = $this->inventoryService->startAllReportExport(Auth::id());

        return response()->json([
            'report_export_id' => $reportExport->id,
            'total'             => $reportExport->total_items,
        ]);
    }

    /**
     * [AJAX] Memproses 1 batch (potongan kecil) Laporan Massal - dipanggil
     * berulang oleh JavaScript sampai seluruh data selesai diproses. Setiap
     * panggilan singkat & ringan, sehingga aman dari timeout di hosting
     * manapun tanpa perlu proses background/queue worker tambahan.
     */
    public function processAllReportBatch(ReportExport $reportExport): \Illuminate\Http\JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data inventory.'
        );

        $progress = $this->inventoryService->processAllReportBatch($reportExport);

        return response()->json($progress);
    }

    /**
     * [AJAX] Membatalkan proses Laporan Massal yang sedang berjalan -
     * menghentikan progresnya dan menghapus total jejaknya (file sementara,
     * file PDF kalau kebetulan sempat jadi, dan baris datanya) sama sekali,
     * tidak ada yang tersimpan.
     */
    public function cancelAllReportExport(ReportExport $reportExport): \Illuminate\Http\JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data inventory.'
        );

        $this->inventoryService->cancelReportExport($reportExport);

        return response()->json(['cancelled' => true]);
    }

    /**
     * Mengunduh hasil Laporan Massal yang sudah selesai diproses di
     * antrean (diakses lewat link di notifikasi navbar).
     */
    public function downloadQueuedReport(ReportExport $reportExport)
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data inventory.'
        );

        abort_unless(
            $reportExport->isReady(),
            404,
            'Laporan belum siap atau gagal diproses.'
        );

        if (!$reportExport->downloaded_at) {
            $reportExport->update(['downloaded_at' => now()]);
        }
        
        return Storage::disk('public')->download(
            $reportExport->file_path
        );
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRequest;
use App\Models\Inventory;
use App\Models\ReportExport;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Service Inventory & Surat Jalan (yang terakhir dipakai khusus untuk
     * endpoint fitur Scan mode Pinjam/Kembalikan/Rusak/Hilang di bawah).
     */
    public function __construct(
        protected InventoryService $inventoryService,
        protected \App\Services\Project\SuratJalanService $suratJalanService
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

        $borrowHistory = $this->inventoryService->getBorrowHistory($inventory);

        return view('inventory.show', compact('inventory', 'borrowHistory'));
    }

    /**
     * Halaman publik (TANPA login) yang tampil saat QR Code di Inventory
     * Report di-scan. Diproteksi middleware 'signed' di route (lihat
     * routes/web.php) - bukan lewat auth/role seperti halaman admin
     * lainnya. View-nya SENGAJA halaman berdiri sendiri (bukan
     * layouts.app) - ringkas, read-only, mobile-first.
     */
    public function scanShow(Inventory $inventory): View
    {
        $inventory->load('attributes', 'units');

        return view('inventory.scan', compact('inventory'));
    }

    /**
     * [AJAX] Fitur Scan Barcode di halaman Inventory List - izin SENDIRI
     * (scan_barang.view), SENGAJA TIDAK ikut permission modul 'inventory'
     * sama sekali. $mode menentukan bentuk responnya:
     * - pinjam: jumlah tersedia (+ daftar peminjam saat ini kalau 0)
     * - kembalikan/rusak/hilang: daftar unit yang SEDANG DIPINJAM (sumber
     *   sama, 3 mode ini beda di endpoint submit-nya saja - lihat di bawah)
     */
    public function scanLookup(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('scan_barang', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk fitur Scan.'
        );

        $request->validate([
            'serial_number' => ['required', 'string'],
            'mode'          => ['required', 'in:pinjam,kembalikan,rusak,hilang'],
        ]);

        $inventory = Inventory::withAvailability()
            ->where('serial_number', trim($request->input('serial_number')))
            ->first();

        if (!$inventory) {
            return response()->json(['found' => false]);
        }

        $mode = $request->input('mode');
        $response = [
            'found' => true,
            'mode' => $mode,
            'inventory' => ['id' => $inventory->id, 'name' => $inventory->name],
        ];

        if ($mode === 'pinjam') {
            $response['available_qty'] = $inventory->qty_available;
            // Stok 0 - tampilkan siapa yang pinjam sekarang (info saja,
            // bukan berarti mode ini jadi bisa dipakai buat mengembalikan).
            $response['borrowed_units'] = $inventory->qty_available > 0
                ? []
                : $this->getBorrowedUnitsPayload($inventory);

            return response()->json($response);
        }

        $response['borrowed_units'] = $this->getBorrowedUnitsPayload($inventory);

        return response()->json($response);
    }

    /**
     * Daftar unit fisik barang ini yang SEDANG DIPINJAM, dipakai scanLookup()
     * di atas untuk mode kembalikan/rusak/hilang, dan mode pinjam saat stok
     * 0. Referensinya bisa Project (Surat Jalan biasa) ATAU nama akun
     * (peminjaman langsung) - lihat SuratJalan::referensiLabel().
     */
    private function getBorrowedUnitsPayload(Inventory $inventory): array
    {
        return $inventory->units()
            ->whereNotNull('surat_jalan_item_id')
            ->with('suratJalanItem.suratJalan')
            ->orderBy('unit_number')
            ->get()
            ->filter(fn ($unit) => $unit->suratJalanItem?->suratJalan)
            ->map(fn ($unit) => [
                'unit_id'           => $unit->id,
                'unit_number'       => $unit->unit_number,
                'referensi'         => $unit->suratJalanItem->suratJalan->referensiLabel(),
                'surat_jalan_nomor' => $unit->suratJalanItem->suratJalan->nomor,
            ])
            ->values()
            ->all();
    }

    /**
     * [AJAX] Submit fitur Scan mode "Pinjam" - bikin peminjaman langsung
     * (tanpa Project, referensi akun yang scan). Lihat
     * SuratJalanService::createDirectLoan().
     */
    public function scanPinjam(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('scan_barang', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk fitur Scan.'
        );

        $data = $request->validate([
            'inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'qty'          => ['required', 'integer', 'min:1'],
        ]);

        try {
            $suratJalan = $this->suratJalanService->createDirectLoan($data['inventory_id'], $data['qty']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'nomor' => $suratJalan->nomor]);
    }

    /**
     * [AJAX] Submit fitur Scan mode "Kembalikan" - lihat
     * SuratJalanService::returnUnitsByIds() (generik, bisa campur Surat
     * Jalan biasa & peminjaman langsung dalam 1x submit).
     */
    public function scanKembalikan(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('scan_barang', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk fitur Scan.'
        );

        $data = $request->validate([
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct', 'exists:inventory_units,id'],
        ]);

        try {
            $this->suratJalanService->returnUnitsByIds($data['unit_ids']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * [AJAX] Submit fitur Scan mode "Rusak"/"Hilang" - lepas dari
     * peminjaman SEKALIGUS ubah status dalam 1 aksi, lihat
     * SuratJalanService::returnAndMarkStatus() (Opsi B).
     */
    public function scanStatus(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('scan_barang', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk fitur Scan.'
        );

        $data = $request->validate([
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct', 'exists:inventory_units,id'],
            'status'     => ['required', 'in:Rusak,Hilang'],
        ]);

        try {
            $this->suratJalanService->returnAndMarkStatus($data['unit_ids'], $data['status']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
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

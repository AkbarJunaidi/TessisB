<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRequest;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
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
     * Menampilkan daftar inventory.
     */
    public function index(): View
    {
        $inventories = $this->inventoryService->getAllPaginated(10);

        return view('inventory.index', compact('inventories'));
    }

    /**
     * Menampilkan halaman tambah inventory.
     */
    public function create(): View
    {
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
     * Menampilkan detail inventory.
     */
    public function show(Inventory $inventory): View
    {
        return view('inventory.show', compact('inventory'));
    }

    /**
     * Menampilkan halaman edit inventory.
     */
    public function edit(Inventory $inventory): View
    {
        return view('inventory.edit', compact('inventory'));
    }

    /**
     * Memperbarui data inventory.
     */
    public function update(
        InventoryRequest $request,
        Inventory $inventory
    ): RedirectResponse {

        $this->inventoryService->updateInventory(
            $inventory,
            $request->validated(),
            $request->file('image')
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Data inventory berhasil diperbarui.');
    }

    /**
     * Menghapus inventory.
     */
    public function destroy(Inventory $inventory): RedirectResponse
    {
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
     * Memicu proses unduhan langsung file PDF Laporan Massal Seluruh Inventaris
     * menjadi satu file utuh dengan pembatas halaman (page-break).
     */
    public function downloadAllPdf()
    {
        return $this->inventoryService->generateAllReport($stream = false);
    }
}

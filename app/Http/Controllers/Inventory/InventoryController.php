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
}
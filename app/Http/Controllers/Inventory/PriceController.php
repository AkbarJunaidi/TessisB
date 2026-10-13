<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PriceRequest;
use App\Models\Inventory;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\RepairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Daftar Harga Barang: harga beli terakhir (dari Pembelian atau diisi manual) dibanding biaya servis per barang.
 */
class PriceController extends Controller
{
    public function __construct(
        protected RepairService $repairService,
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        // Harga berasal dari modul Pembelian, jadi izin lihat Pembelian ikut disyaratkan.
        abort_unless(
            $user?->hasPermission('inventory', 'view') && $user->hasPermission('purchase', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat daftar harga barang.'
        );

        $filters = $request->only(['search', 'filter']);
        $canEdit = (bool) $user->hasPermission('inventory', 'edit');

        return view('inventory.price.index', $this->repairService->getPriceList($filters) + compact('filters', 'canEdit'));
    }

    public function update(PriceRequest $request, Inventory $inventory): RedirectResponse
    {
        $harga  = $request->validated('harga_manual');
        $ambang = $request->validated('ambang_ganti_persen');

        $this->inventoryService->updateHargaManual($inventory, $harga);
        $this->inventoryService->updateAmbangGanti($inventory, $ambang === null ? null : (int) $ambang);

        return back()->with('success', $harga === null
            ? "Pengaturan \"{$inventory->name}\" disimpan."
            : "Harga \"{$inventory->name}\" diperbarui sebagai harga manual.");
    }
}

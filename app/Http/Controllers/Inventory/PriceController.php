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

        $filters   = $request->only(['search', 'filter', 'biaya']);
        $canEdit   = (bool) $user->hasPermission('inventory', 'edit');

        return view('inventory.price.index', $this->repairService->getPriceList($filters) + compact('filters', 'canEdit'));
    }

    public function update(PriceRequest $request, Inventory $inventory): RedirectResponse
    {
        // Harga dari Pembelian diterima selalu menang, jadi harga manual tidak boleh diubah dari sini.
        if (($this->repairService->latestPrice($inventory)['source'] ?? null) === 'pembelian') {
            return back()->with('error', "Harga \"{$inventory->name}\" mengikuti Pembelian terakhir dan diubah lewat Pembelian.");
        }

        $this->inventoryService->updateHargaManual($inventory, $request->validated('harga_manual'));

        return back()->with('success', $request->validated('harga_manual') === null
            ? "Harga \"{$inventory->name}\" dikosongkan."
            : "Harga \"{$inventory->name}\" disimpan.");
    }
}

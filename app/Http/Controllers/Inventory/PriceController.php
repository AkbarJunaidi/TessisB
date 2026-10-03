<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\RepairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Daftar Harga Barang: harga beli terakhir (dari Pembelian) dibanding biaya servis per barang.
 */
class PriceController extends Controller
{
    public function __construct(
        protected RepairService $repairService
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

        return view('inventory.price.index', $this->repairService->getPriceList($filters) + compact('filters'));
    }
}

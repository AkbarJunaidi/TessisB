<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman "Mutasi Aset" - buku besar lintas SEMUA barang (lihat
 * InventoryMutationService). BEDA dari Riwayat Peminjaman di Inventory
 * Detail (itu 1 barang saja, pinjam/kembali saja, tetap terpisah).
 */
class InventoryMutationController extends Controller
{
    public function __construct(
        protected InventoryMutationService $mutationService
    ) {}

    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat mutasi aset.'
        );

        $filters = $request->only(['search', 'from', 'to']);
        $filters['event_types'] = $request->input('event_types', []);

        $mutations = $this->mutationService->getFiltered($filters, 25);

        return view('inventory.mutation.index', compact('mutations', 'filters'));
    }
}

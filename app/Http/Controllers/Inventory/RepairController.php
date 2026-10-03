<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CompleteRepairRequest;
use App\Http\Requests\Inventory\RepairRequest;
use App\Models\Contact;
use App\Models\InventoryRepair;
use App\Models\User;
use App\Services\Inventory\RepairService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Perbaikan Barang: catatan pengiriman unit ke tempat servis (vendor / toko / bengkel).
 */
class RepairController extends Controller
{
    public function __construct(
        protected RepairService $repairService
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeView();

        $filters = $request->only(['search', 'status']);
        $repairs = $this->repairService->getFiltered($filters);
        $stats   = $this->repairService->getStats();

        return view('inventory.repair.index', compact('repairs', 'stats', 'filters'));
    }

    public function create(Request $request): View
    {
        $this->authorizeManage();

        $inventories = $this->repairService->getRepairableInventories();
        $vendors     = Contact::vendors()->orderBy('name')->get(['id', 'name', 'company', 'address']);
        $people      = User::orderBy('name')->pluck('name');

        // Dari tombol "Kirim ke Servis" di Detail Inventory: unit sudah tercentang.
        $preselected = array_map('intval', (array) $request->query('unit_ids', []));

        // Harga beli berasal dari Pembelian, jadi perbandingan hanya untuk yang boleh melihat Pembelian.
        $repairCosts = Auth::user()?->hasPermission('purchase', 'view') ? $this->repairService->costComparison() : [];

        return view('inventory.repair.create', compact('inventories', 'vendors', 'people', 'preselected', 'repairCosts'));
    }

    public function store(RepairRequest $request): RedirectResponse
    {
        try {
            $repair = $this->repairService->create($request->validated());

            return redirect()
                ->route('inventory.repairs.show', $repair)
                ->with('success', "Perbaikan {$repair->code} berhasil dicatat. Unit berstatus Perbaikan di {$repair->tempat_nama}.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(InventoryRepair $repair): View
    {
        $this->authorizeView();

        $repair->load(['vendor', 'items.inventory', 'items.unit', 'creator:id,name', 'completer:id,name']);

        $people    = User::orderBy('name')->pluck('name');
        $canManage = (bool) Auth::user()?->hasPermission('inventory', 'manage_repairs');

        return view('inventory.repair.show', compact('repair', 'people', 'canManage'));
    }

    public function complete(CompleteRepairRequest $request, InventoryRepair $repair): RedirectResponse
    {
        try {
            $this->repairService->complete($repair, $request->validated());

            return redirect()
                ->route('inventory.repairs.show', $repair)
                ->with('success', "Perbaikan {$repair->code} diselesaikan.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancel(InventoryRepair $repair): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $this->repairService->cancel($repair);

            return redirect()
                ->route('inventory.repairs.show', $repair)
                ->with('success', "Catatan perbaikan {$repair->code} dibatalkan. Unit dikembalikan ke status sebelumnya.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function authorizeView(): void
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data perbaikan barang.'
        );
    }

    private function authorizeManage(): void
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'manage_repairs'),
            403,
            'Anda tidak memiliki hak akses untuk mengelola perbaikan barang.'
        );
    }
}

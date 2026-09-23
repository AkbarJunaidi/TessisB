<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tracking\ReturnBorrowedUnitsRequest;
use App\Models\Project;
use App\Services\Tracking\BorrowedItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BorrowedItemController extends Controller
{
    public function __construct(
        protected BorrowedItemService $borrowedItemService
    ) {}

    /**
     * Halaman daftar project & akun (peminjaman langsung) yang masih punya
     * barang dipinjam (Accordion).
     */
    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('borrowed_items', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Barang Pinjaman.'
        );

        $projects = $this->borrowedItemService->getProjectsWithBorrowedItems();
        $unitsByProject = $this->borrowedItemService->getBorrowedUnitsGroupedByProject($projects);

        $peminjamLangsung = $this->borrowedItemService->getUsersWithDirectLoans();
        $unitsByUser = $this->borrowedItemService->getBorrowedUnitsGroupedByUser($peminjamLangsung);

        return view('tracking.borrowed-items.index', compact(
            'projects',
            'unitsByProject',
            'peminjamLangsung',
            'unitsByUser'
        ));
    }

    /**
     * [AJAX] Konfirmasi pengembalian sejumlah unit yang dipilih user untuk 1 project.
     * Mengembalikan data terbaru project ini (dipakai untuk refresh accordion di
     * sisi client tanpa reload seluruh halaman).
     */
    public function returnUnits(ReturnBorrowedUnitsRequest $request, Project $project): JsonResponse
    {
        try {
            $result = $this->borrowedItemService->returnUnits(
                $project,
                $request->validated()['unit_ids']
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $remainingUnits = $result['project_still_has_borrowed']
            ? $this->borrowedItemService->getBorrowedUnitsForProject($project)
            : collect();

        return response()->json([
            'message' => 'Barang berhasil dikembalikan.',
            'project_still_has_borrowed' => $result['project_still_has_borrowed'],
            'remaining_units_count' => $remainingUnits->count(),
            'units' => $remainingUnits->map(fn ($unit) => [
                'id' => $unit->id,
                'inventory_name' => $unit->inventory->name ?? '-',
                'unit_number' => $unit->unit_number,
                'surat_jalan_nomor' => $unit->suratJalanItem->suratJalan->nomor ?? '-',
            ]),
        ]);
    }

    /**
     * [AJAX] Konfirmasi untuk unit yang di-cycle sampai "Dikembalikan" di
     * halaman Barang Pinjaman - GENERIK, tidak terikat 1 Project (beda dari
     * returnUnits() di atas), bisa campur Project & peminjaman langsung
     * dalam 1x klik Konfirmasi. Izin sama seperti returnUnits() di atas
     * (borrowed_items.process_return, lewat Form Request), TIDAK pakai
     * izin scan_barang sama sekali walau logic-nya dipakai bareng fitur Scan.
     */
    public function returnByIds(ReturnBorrowedUnitsRequest $request): JsonResponse
    {
        try {
            $this->borrowedItemService->returnByIds($request->validated()['unit_ids']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Barang berhasil dikembalikan.']);
    }

    /**
     * [AJAX] Konfirmasi untuk unit yang di-cycle sampai "Rusak"/"Hilang" di
     * halaman Barang Pinjaman - lepas dari peminjaman SEKALIGUS ubah status.
     */
    public function markStatus(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('borrowed_items', 'process_return'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah status barang pinjaman.'
        );

        $data = $request->validate([
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct', 'exists:inventory_units,id'],
            'status'     => ['required', 'in:Rusak,Hilang'],
        ]);

        try {
            $this->borrowedItemService->markStatus($data['unit_ids'], $data['status']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Status barang berhasil diubah.']);
    }
}

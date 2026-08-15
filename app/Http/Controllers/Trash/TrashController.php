<?php

namespace App\Http\Controllers\Trash;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trash\TrashFilterRequest;
use App\Services\Trash\TrashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class TrashController extends Controller
{
    public function __construct(
        protected TrashService $trashService
    ) {}

    /**
     * Menampilkan halaman Trash.
     */
    public function index(TrashFilterRequest $request): View
    {
        $filters = $request->validated();

        $trashItems = $this->trashService->getFilteredTrash($filters);

        $types = $this->trashService->getTypeOptions();

        $users = $this->trashService->getUsersForFilter();

        return view(
            'trash.index',
            compact('trashItems', 'types', 'users', 'filters')
        );
    }

    /**
     * Memulihkan 1 data dari Trash (restore()).
     * Dapat diakses Super Admin & Admin, sesuai gate role pada route.
     */
    public function restore(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->trashService->restore($type, $id);

            $message = "Data \"{$result['name']}\" berhasil dipulihkan.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => $result,
                ]);
            }

            return back()->with('success', $message);

        } catch (InvalidArgumentException $e) {
            return $this->fail($request, $e->getMessage(), 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->fail($request, 'Data yang dituju tidak ditemukan.', 404);
        } catch (RuntimeException $e) {
            return $this->fail($request, $e->getMessage(), 409);
        }
    }

    /**
     * Menghapus 1 data secara permanen (forceDelete()).
     * HANYA Super Admin — otorisasi ganda: role gate di route + pengecekan di sini.
     */
    public function forceDelete(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        if (!Auth::user()?->isSuperAdmin()) {
            return $this->fail($request, 'Hanya Super Admin yang dapat menghapus data secara permanen.', 403);
        }

        try {
            $result = $this->trashService->forceDelete($type, $id);

            $message = "Data \"{$result['name']}\" berhasil dihapus permanen.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => $result,
                ]);
            }

            return back()->with('success', $message);

        } catch (InvalidArgumentException $e) {
            return $this->fail($request, $e->getMessage(), 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->fail($request, 'Data yang dituju tidak ditemukan.', 404);
        } catch (RuntimeException $e) {
            return $this->fail($request, $e->getMessage(), 409);
        }
    }

    /**
     * Helper seragam untuk respons gagal (JSON untuk AJAX, redirect untuk fallback).
     */
    private function fail(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}

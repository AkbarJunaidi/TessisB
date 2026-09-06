<?php

namespace App\Http\Controllers\ActivityLog;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\ActivityLogDeleteRangeRequest;
use App\Http\Requests\ActivityLog\ActivityLogFilterRequest;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Menampilkan halaman Activity Log.
     */
    public function index(ActivityLogFilterRequest $request): View
    {
        $filters = $request->validated();

        $logs = $this->activityLogService
            ->getFilteredLogs($filters);

        $users = $this->activityLogService
            ->getAllUsersForFilter();

        $modules = $this->activityLogService
            ->getModules();

        $actions = $this->activityLogService
            ->getActions();

        return view(
            'activity-log.index',
            compact(
                'logs',
                'users',
                'modules',
                'actions',
                'filters'
            )
        );
    }

    /**
     * Menghapus seluruh Activity Log dalam rentang tanggal tertentu.
     * HANYA Super Admin - otorisasi ganda: role gate di route + pengecekan
     * di sini (mengikuti pola yang sama dengan TrashController::forceDelete()).
     */
    public function deleteRange(ActivityLogDeleteRangeRequest $request): JsonResponse|RedirectResponse
    {
        if (!Auth::user()?->isSuperAdmin()) {
            return $this->fail($request, 'Hanya Super Admin yang dapat menghapus Activity Log.', 403);
        }

        $validated = $request->validated();

        $deletedCount = $this->activityLogService->deleteByDateRange(
            $validated['date_from'],
            $validated['date_to']
        );

        $message = $deletedCount > 0
            ? "Berhasil menghapus {$deletedCount} data Activity Log pada rentang tanggal tersebut."
            : 'Tidak ada data Activity Log yang ditemukan pada rentang tanggal tersebut.';

        if ($request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => $message,
                'deleted_count' => $deletedCount,
            ]);
        }

        return back()->with('success', $message);
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

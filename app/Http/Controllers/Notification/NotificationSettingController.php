<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationSettingUpdateRequest;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola jenis notifikasi OTOMATIS (dihitung dari data, lihat
 * NotificationService) - Super Admin bisa nonaktifkan jenis tertentu
 * atau mengubah urutan tampilnya di navbar. BEDA dari AnnouncementController
 * (itu untuk pengumuman TULISAN Super Admin sendiri, bukan yang otomatis).
 */
class NotificationSettingController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * HANYA Super Admin (role gate di route, dicek ulang di sini
     * mengikuti pola otorisasi ganda yang sama dengan controller lain
     * di modul ini).
     *
     * Dipanggil lewat AJAX dari halaman Notifikasi (fetch, tanpa reload
     * halaman) - balas JSON kalau diminta ($request->wantsJson(), true
     * saat header Accept: application/json dikirim JS). Fallback redirect
     * biasa tetap dipertahankan (progressive enhancement) untuk skenario
     * JS mati/gagal dimuat, supaya fitur tidak benar-benar rusak total.
     */
    public function update(NotificationSettingUpdateRequest $request): JsonResponse|RedirectResponse
    {
        if (!Auth::user()?->isSuperAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hanya Super Admin yang dapat mengubah pengaturan ini.'], 403);
            }
            return back()->with('error', 'Hanya Super Admin yang dapat mengubah pengaturan ini.');
        }

        $validated = $request->validated();

        $this->notificationService->updateTypeSettings(
            $validated['ordered_types'],
            $validated['enabled_types'] ?? []
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengaturan notifikasi berhasil disimpan.']);
        }

        return back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }
}

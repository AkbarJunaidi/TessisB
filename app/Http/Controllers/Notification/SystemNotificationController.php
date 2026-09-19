<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Sematkan/lepas-sematan/hapus 1 notifikasi OTOMATIS (password reset,
 * report ready, unpaid deadline, finance missing) - BEDA dari
 * AnnouncementController (itu untuk Pengumuman, punya baris tersimpan
 * sungguhan). Notifikasi otomatis dihitung ulang dari data setiap saat,
 * jadi "hapus" di sini artinya disembunyikan dari tampilan user yang
 * login (lihat NotificationService::dismissSystemNotification()).
 *
 * Semua aksi di sini otomatis scoped ke user yang login sendiri
 * (Auth::id() dikirim ke Service, disimpan di kolom user_id) - tidak
 * ada input ID user dari luar yang bisa dimanipulasi.
 */
class SystemNotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function pin(string $key): JsonResponse
    {
        $this->notificationService->pinSystemNotification(Auth::id(), $key);

        return response()->json(['success' => true]);
    }

    public function unpin(string $key): JsonResponse
    {
        $this->notificationService->unpinSystemNotification(Auth::id(), $key);

        return response()->json(['success' => true]);
    }

    public function dismiss(string $key): JsonResponse
    {
        $this->notificationService->dismissSystemNotification(Auth::id(), $key);

        return response()->json(['success' => true]);
    }
}

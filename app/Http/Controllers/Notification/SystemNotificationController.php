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
 * sungguhan). Notifikasi otomatis dihitung ulang dari data setiap saat.
 *
 * Sematkan/lepas-sematan = preferensi PRIBADI (scoped ke Auth::id()).
 * Hapus = benar-benar hilang untuk SEMUA user (lihat
 * NotificationService::deleteSystemNotification()), jadi digate
 * permission 'notifikasi_sistem.delete' (default hanya Super Admin,
 * Admin bisa diberi akses lewat Permission Override).
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

    public function delete(string $key): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('notifikasi_sistem', 'delete'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus notifikasi sistem.'
        );

        $this->notificationService->deleteSystemNotification(Auth::id(), $key);

        return response()->json(['success' => true]);
    }
}

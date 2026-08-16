<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Daftar notifikasi aktif untuk navbar. Dipanggil lewat AJAX (fetch)
     * dari navbar.blade.php, di-poll berkala oleh browser.
     *
     * Hanya Super Admin & Admin - Employee tidak pernah melihat widget ini
     * di navbar (link disembunyikan), dan endpoint ini menolak mereka juga
     * kalau diakses langsung.
     */
    public function active(): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasRole('super_admin', 'admin'),
            403
        );

        return response()->json([
            'notifications' => $this->notificationService->getActiveNotifications(),
        ]);
    }
}

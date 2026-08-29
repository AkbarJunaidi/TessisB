<?php

namespace App\Services;

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Alur "Lupa Password" di project ini BUKAN reset link via email, melainkan
 * permintaan yang tercatat di sistem dan perlu ditindaklanjuti manual oleh
 * Super Admin lewat halaman User Management (lihat notifikasi navbar & badge
 * di User Management).
 *
 * Sengaja tidak membocorkan apakah sebuah email terdaftar atau tidak:
 * requestReset() selalu diperlakukan sama oleh Controller (pesan sukses yang
 * sama ke pengunjung) - hanya kalau email memang cocok, permintaan benar-benar
 * dibuat dan Super Admin diberi tahu.
 */
class PasswordResetRequestService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Memproses permintaan lupa password dari halaman publik.
     *
     * Jika email cocok dengan user yang ada (dan belum ada permintaan
     * pending sebelumnya untuk user yang sama) -> buat 1 baris permintaan
     * baru, supaya muncul di notifikasi Super Admin & badge User Management.
     * Jika email tidak cocok -> tidak melakukan apa pun.
     */
    public function requestReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        $alreadyPending = PasswordResetRequest::pending()
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyPending) {
            return;
        }

        PasswordResetRequest::create([
            'user_id' => $user->id,
            'email'   => $user->email,
            'status'  => 'pending',
        ]);

        // Invalidate cache notifikasi navbar (lihat NotificationService),
        // supaya Super Admin langsung melihat permintaan ini tanpa menunggu
        // cache lama kedaluwarsa.
        Cache::forget('notifications.active');

        $this->activityLogService->log(
            $user->id,
            'Authentication',
            'Password Reset Requested'
        );
    }

    /**
     * Menyelesaikan seluruh permintaan pending milik seorang user - dipanggil
     * otomatis saat Super Admin mereset password user tersebut, supaya badge
     * "Lupa Password" hilang tanpa perlu langkah manual terpisah.
     */
    public function resolveForUser(int $userId, int $resolvedBy): void
    {
        PasswordResetRequest::pending()
            ->where('user_id', $userId)
            ->update([
                'status'      => 'resolved',
                'resolved_by' => $resolvedBy,
                'resolved_at' => Carbon::now(),
            ]);

        // Invalidate cache notifikasi navbar, sama seperti requestReset().
        Cache::forget('notifications.active');
    }

    /**
     * ID seluruh user yang punya permintaan pending saat ini - dipakai untuk
     * menampilkan badge "Lupa Password" di daftar User Management.
     *
     * @return array<int, int>
     */
    public function pendingUserIds(): array
    {
        return PasswordResetRequest::pending()
            ->pluck('user_id')
            ->all();
    }

    /**
     * Permintaan pending terbaru milik satu user tertentu (kalau ada) -
     * dipakai di halaman Detail User untuk menampilkan alert & tombol aksi.
     */
    public function latestPendingFor(int $userId): ?PasswordResetRequest
    {
        return PasswordResetRequest::pending()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Seluruh permintaan pending saat ini, beserta relasi user - dipakai
     * untuk membangun notifikasi navbar Super Admin/Admin.
     *
     * @return Collection<int, PasswordResetRequest>
     */
    public function pendingWithUser(int $limit = 10): Collection
    {
        return PasswordResetRequest::pending()
            ->with('user:id,name,email')
            ->whereHas('user')
            ->latest()
            ->limit($limit)
            ->get();
    }
}

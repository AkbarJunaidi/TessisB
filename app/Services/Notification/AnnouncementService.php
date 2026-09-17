<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Pengumuman TIDAK punya tabel sendiri - murni memanfaatkan sistem
 * Database Notification bawaan Laravel (tabel `notifications`, lihat
 * migration create_notifications_table & trait Notifiable di model User).
 * Kolom `pinned_at` (migration add_pinned_at_to_notifications_table)
 * adalah tambahan khusus TessisB di atas skema standar itu, dipakai
 * untuk fitur sematkan (lihat pin()/unpin() di bawah).
 *
 * Kenapa ini sudah cukup tanpa tabel terpisah: Super Admin yang mengirim
 * SELALU ikut jadi salah satu penerima juga (lihat send() di bawah), jadi
 * "riwayat pengumuman yang pernah dikirim" otomatis muncul bersih (1 baris
 * per pengumuman, bukan dobel) lewat daftar notifikasi miliknya SENDIRI -
 * tidak perlu tabel/query terpisah untuk itu.
 *
 * Semua method di sini yang menyentuh 1 notifikasi spesifik (markAsRead,
 * pin, unpin, delete) SENGAJA mencari lewat relasi $user->notifications()
 * (bukan query tabel notifications langsung) - otomatis aman dari user
 * lain memanipulasi notifikasi milik user lain, karena relasi ini sudah
 * terfilter ke notifiable_id/type user yang sedang login.
 */
class AnnouncementService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Semua notifikasi (Pengumuman) milik user yang sedang login -
     * dibatasi 24 JAM TERAKHIR, KECUALI yang sudah disematkan (pinned_at
     * terisi - itulah gunanya sematkan, supaya tidak ikut hilang lewat
     * batas 24 jam). Yang disematkan selalu tampil paling atas, baru
     * diikuti sisanya dari yang terbaru. Lihat class doc di atas untuk
     * kenapa ini juga otomatis berfungsi sebagai "riwayat terkirim".
     */
    public function getInbox(int $perPage = 15)
    {
        return Auth::user()
            ->notifications()
            ->where(function ($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                    ->orWhereNotNull('pinned_at');
            })
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('pinned_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    /**
     * Tandai 1 notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(string $notificationId): bool
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    /**
     * Sematkan/lepas-sematkan 1 notifikasi - MURNI preferensi tampilan
     * milik user yang login sendiri (tidak memengaruhi tampilan user
     * lain sama sekali, karena setiap penerima punya baris `notifications`
     * masing-masing).
     */
    public function pin(string $notificationId): bool
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->forceFill(['pinned_at' => now()])->save();

        return true;
    }

    public function unpin(string $notificationId): bool
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->forceFill(['pinned_at' => null])->save();

        return true;
    }

    /**
     * Hapus 1 notifikasi dari daftar milik user yang login. Ini menghapus
     * SALINAN notifikasi milik user ini saja (1 baris di tabel
     * `notifications`) - TIDAK menghapus pengumuman itu dari kotak
     * notifikasi penerima lain, karena tiap penerima punya baris sendiri.
     *
     * Catatan: siapa boleh menghapus PENGUMUMAN untuk SEMUA orang (bukan
     * cuma salinan sendiri) belum ada aturannya - itu bagian dari matriks
     * izin kirim/hapus yang lebih detail yang masih perlu didesain lebih
     * lanjut nanti, belum dibangun di sini.
     */
    public function delete(string $notificationId): bool
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->delete();

        return true;
    }

    /**
     * Kirim pengumuman ke semua user berstatus aktif - TERMASUK pengirim
     * sendiri (lihat class doc di atas kenapa ini disengaja).
     *
     * SIAPA BOLEH MENGIRIM: untuk saat ini masih HANYA Super Admin (dicek
     * di AnnouncementController::store(), bukan di sini) - matriks izin
     * yang lebih detail ("user A boleh kirim, user B tidak boleh kirim
     * tapi boleh hapus", dst) masih perlu didesain & dibangun terpisah,
     * belum ada di versi ini.
     */
    public function send(string $title, string $message): Collection
    {
        $sender = Auth::user();

        $recipients = User::where('status', 'active')->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new AnnouncementNotification(
                title: $title,
                message: $message,
                sentByName: $sender->name,
            ));
        }

        $this->activityLogService->log(
            $sender->id,
            'User Management',
            "Mengirim pengumuman \"{$title}\" ke {$recipients->count()} user"
        );

        return $recipients;
    }
}

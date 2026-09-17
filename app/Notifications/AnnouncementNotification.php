<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Pengumuman yang dikirim Super Admin lewat halaman Notifikasi
 * (lihat AnnouncementController::store()).
 *
 * SENGAJA TIDAK implements ShouldQueue (beda dari versi sebelumnya) -
 * kalau notifikasi diantrikan tapi tidak ada queue worker yang jalan
 * (`php artisan queue:work`, butuh setup terpisah - Supervisor/systemd,
 * dst), pengiriman jadi diam-diam tidak pernah diproses sama sekali:
 * request "Kirim" tetap sukses & redirect balik dengan pesan berhasil,
 * tapi job-nya numpuk selamanya di tabel `jobs`, tidak ada satupun
 * notifikasi yang benar-benar terkirim - persis gejala yang dilaporkan
 * ("sudah dicoba, tidak ada notifikasi yang muncul"). Dikirim LANGSUNG
 * (synchronous) di sini - untuk jumlah user yang wajar (puluhan), ini
 * cepat dan tidak butuh infrastruktur tambahan apa pun untuk berfungsi.
 *
 * Channel WebPush (notifikasi asli tembus ke Chrome/Firefox dkk, bukan
 * cuma dalam aplikasi) SENGAJA dicek pakai class_exists() dulu di
 * via() - paket `laravel-notification-channels/webpush` belum tentu
 * sudah di-install (lihat README paket ini untuk cara install-nya).
 * Tanpa pengecekan ini, kalau paketnya belum terpasang, method via()
 * akan mereferensikan class yang tidak ada dan bikin FATAL ERROR
 * setiap kali ada yang kirim pengumuman - channel 'database' (inbox
 * dalam aplikasi) harus tetap jalan normal terlepas dari itu.
 */
class AnnouncementNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $sentByName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (class_exists(\NotificationChannels\WebPush\WebPushChannel::class)) {
            $channels[] = \NotificationChannels\WebPush\WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Data yang disimpan di kolom `data` (JSON) tabel notifications -
     * dibaca lagi oleh halaman Notifikasi (inbox) untuk ditampilkan.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'     => $this->title,
            'message'   => $this->message,
            'sent_by'   => $this->sentByName,
        ];
    }

    /**
     * Cuma benar-benar dipanggil Laravel kalau WebPushChannel ada di
     * hasil via() di atas (jadi aman walau method ini tetap ada di file
     * meski paketnya belum terinstall - tidak pernah dieksekusi tanpa
     * class-nya tersedia lebih dulu).
     *
     * @return mixed
     */
    public function toWebPush(object $notifiable, object $notification): mixed
    {
        if (!class_exists(\NotificationChannels\WebPush\WebPushMessage::class)) {
            return null;
        }

        return (new \NotificationChannels\WebPush\WebPushMessage())
            ->title($this->title)
            ->icon('/image/logo1.png')
            ->body($this->message)
            ->data(['notification_id' => $notification->id])
            ->options(['TTL' => 1000]);
    }
}

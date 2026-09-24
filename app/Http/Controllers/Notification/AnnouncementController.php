<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\AnnouncementStoreRequest;
use App\Services\Notification\AnnouncementService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Halaman Notifikasi - SEMUA role bisa akses (role gate DIHAPUS dari
     * route, sebelumnya cuma Super Admin). Isinya menyesuaikan role:
     * - SEMUA role: daftar Pengumuman miliknya sendiri (bisa disematkan/
     *   dihapus - lihat AnnouncementService), ini yang menjawab "notifikasi
     *   tidak terlihat jelas di navbar" karena di sini ruangnya lega.
     * - HANYA Super Admin/Admin: item 4 jenis notifikasi otomatis
     *   (password reset, dst) ikut tampil DI DALAM daftar Pengumuman yang
     *   sama (1 card "Semua Notifikasi", bukan card terpisah lagi - lihat
     *   view). Bisa disematkan (pribadi) oleh siapa saja; tombol hapus
     *   (GLOBAL, semua user) cuma muncul kalau punya permission
     *   'notifikasi_sistem.delete' (lihat $canDeleteSystemNotification).
     * - HANYA Super Admin: form kirim pengumuman baru + panel kelola
     *   jenis notifikasi otomatis (lihat kondisi role di view-nya sendiri).
     */
    public function index(): View
    {
        $user = Auth::user();
        $inbox = $this->announcementService->getInbox();
        $manageableTypes = $this->notificationService->getManageableTypes();

        // 4 jenis otomatis (bukan pengumuman), digabung ke daftar Pengumuman
        // di view - cuma relevan buat Super Admin/Admin, employee tidak
        // pernah lihat 4 jenis ini di lonceng navbar-nya juga.
        $systemNotifications = [];
        if ($user->hasRole('super_admin', 'admin')) {
            $systemNotifications = array_values(array_filter(
                $this->notificationService->getActiveNotifications($user->id),
                fn ($item) => $item['type'] !== 'announcement'
            ));
        }

        // Tombol hapus notifikasi sistem cuma dimunculkan kalau user ini
        // punya izinnya (default Super Admin, Admin lewat Permission
        // Override) - otorisasi sungguhan tetap dicek ulang di controller.
        $canDeleteSystemNotification = (bool) $user->hasPermission('notifikasi_sistem', 'delete');

        // Dikirim juga ke view supaya bisa dipasang di JS registrasi Web
        // Push (lihat resources/views/layouts/app.blade.php) - null kalau
        // paket laravel-notification-channels/webpush belum di-install/
        // di-setup, ditangani dengan aman di sisi JS (tombol "Aktifkan
        // Notifikasi" otomatis disembunyikan).
        $vapidPublicKey = config('webpush.vapid.public_key');

        return view('notification.index', compact(
            'inbox',
            'vapidPublicKey',
            'manageableTypes',
            'systemNotifications',
            'canDeleteSystemNotification'
        ));
    }

    /**
     * Kirim pengumuman baru ke semua user aktif. HANYA Super Admin (role
     * gate di route) - dicek ulang di sini mengikuti pola otorisasi ganda
     * yang sama dengan ActivityLogController::deleteRange().
     */
    public function store(AnnouncementStoreRequest $request): RedirectResponse
    {
        if (!Auth::user()?->hasPermission('notifikasi_sistem', 'kirim')) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk mengirim pengumuman.');
        }

        $validated = $request->validated();

        $recipients = $this->announcementService->send($validated['title'], $validated['message']);

        return back()->with('success', "Pengumuman berhasil dikirim ke {$recipients->count()} user.");
    }

    /**
     * Tandai 1 notifikasi sebagai sudah dibaca (AJAX, dipanggil saat item
     * di kotak masuk diklik).
     */
    public function markAsRead(string $id): JsonResponse
    {
        $marked = $this->announcementService->markAsRead($id);

        return response()->json(['success' => $marked]);
    }

    public function markAllAsRead(): RedirectResponse
    {
        $this->announcementService->markAllAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Sematkan/hapus 1 notifikasi (Pengumuman) - TERSEDIA UNTUK SEMUA
     * role yang login, karena ini cuma memengaruhi salinan milik user itu
     * sendiri (lihat AnnouncementService::pin()/unpin()/delete() - aman
     * dijamin lewat relasi $user->notifications(), bukan query bebas).
     *
     * Catatan: ini BUKAN matriks izin "siapa boleh kirim/hapus untuk
     * semua orang" yang lebih detail - itu masih perlu didesain terpisah
     * nanti (lihat komentar di AnnouncementService::send()/delete()).
     */
    public function pin(string $id): JsonResponse
    {
        return response()->json(['success' => $this->announcementService->pin($id)]);
    }

    public function unpin(string $id): JsonResponse
    {
        return response()->json(['success' => $this->announcementService->unpin($id)]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['success' => $this->announcementService->delete($id)]);
    }

    /**
     * Simpan push subscription browser (endpoint + kunci enkripsi) supaya
     * user ini bisa menerima Web Push - dipanggil dari JS setelah user
     * mengizinkan notifikasi (lihat layouts/app.blade.php). TERSEDIA UNTUK
     * SEMUA role yang login (bukan cuma Super Admin) - siapa pun boleh
     * mendaftarkan browsernya untuk menerima pengumuman.
     *
     * Method updatePushSubscription() datang dari trait
     * HasPushSubscriptions milik paket laravel-notification-channels/
     * webpush - dicek method_exists() dulu supaya endpoint ini tidak fatal
     * error kalau paketnya belum di-install (lihat README paket ini).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!method_exists($user, 'updatePushSubscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Web Push belum diaktifkan di server (paket belum terpasang).',
            ], 501);
        }

        $validated = $request->validate([
            'endpoint'         => ['required', 'string'],
            'keys.p256dh'      => ['required', 'string'],
            'keys.auth'        => ['required', 'string'],
            'contentEncoding'  => ['nullable', 'string'],
        ]);

        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['contentEncoding'] ?? null,
        );

        return response()->json(['success' => true]);
    }

    /**
     * Hapus push subscription (dipanggil kalau user menonaktifkan
     * notifikasi lewat browser, supaya server berhenti mencoba kirim ke
     * endpoint yang sudah tidak mau menerima).
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!method_exists($user, 'deletePushSubscription')) {
            return response()->json(['success' => false], 501);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $user->deletePushSubscription($validated['endpoint']);

        return response()->json(['success' => true]);
    }
}

<?php

use App\Http\Controllers\ActivityLog\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetRequestController;
use App\Http\Controllers\Contact\ContactController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\DataIntegration\FileController;
use App\Http\Controllers\DataIntegration\FolderController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\InventoryMutationController;
use App\Http\Controllers\Notification\AnnouncementController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Notification\NotificationSettingController;
use App\Http\Controllers\Notification\SystemNotificationController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectNoteController;
use App\Http\Controllers\Report\FinancialReportController;
use App\Http\Controllers\Project\SuratJalanController;
use App\Http\Controllers\Search\SearchController;
use App\Http\Controllers\Task\CommentController;
use App\Http\Controllers\Task\TaskController;
use App\Http\Controllers\Tracking\BorrowedItemController;
use App\Http\Controllers\Trash\TrashController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

// Redirect halaman utama ke login
Route::redirect('/', '/login');

// Group rute untuk tamu (Guest) - Belum Login
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Rate limit per IP (lapisan kedua) sekarang ditangani penuh di dalam
    // AuthService::login() - lihat MAX_IP_ATTEMPTS/IP_LOCKOUT_DECAY_SECONDS -
    // supaya pesan errornya konsisten & terintegrasi dengan tampilan login
    // (termasuk hitung mundur otomatis), bukan halaman error 429 generik
    // bawaan middleware `throttle`.
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    // Modul Lupa Password - permintaan (bukan reset link email), lihat
    // PasswordResetRequestService untuk alur lengkapnya.
    Route::get('/forgot-password', [PasswordResetRequestController::class, 'create'])
        ->name('forgot-password');

    Route::post('/forgot-password', [PasswordResetRequestController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('forgot-password.store');

});

// Halaman Scan QR Inventory Report - SENGAJA di luar grup 'auth' & 'guest'
// (bisa diakses siapa saja, login ataupun tidak), karena ini yang dibuka
// saat QR di Inventory Report yang dicetak fisik di-scan pakai kamera HP.
// Keamanannya BUKAN dari login, tapi dari middleware 'signed' (URL harus
// persis yang di-generate sistem, ada tanda tangannya - coba tebak-tebak
// ID lain di URL tidak akan valid). Lihat QrCodeService::generateFromUrl()
// & InventoryService (pemanggil URL::signedRoute() saat create Inventory).
Route::get('scan/inventory/{inventory}', [InventoryController::class, 'scanShow'])
    ->name('inventory.scan')
    ->middleware('signed');

// Group rute terproteksi (Auth) - Harus Login Terlebih Dahulu
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Pencarian global dari ikon search di navbar. Tidak digating role
    // tertentu di sini - tiap kategori hasil sudah digating permission
    // modulnya masing-masing di dalam GlobalSearchService, jadi user
    // tanpa permission apa pun tetap boleh membuka halaman ini (cuma akan
    // melihat "tidak ada hasil").
    Route::get('/search', [SearchController::class, 'index'])
        ->name('search.index');

    // Endpoint JSON untuk dropdown saran (typeahead) ikon search navbar -
    // dipanggil lewat fetch() saat user mengetik (lihat navbar.blade.php).
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->name('search.suggest');

    // Modul Inventory Management
    // Route scan-lookup SENGAJA didaftarkan SEBELUM Route::resource di bawah
    // - kalau ditaruh setelah, "scan-lookup" akan ketangkap sebagai parameter
    // {inventory} pada route show resource (inventory/{inventory}).
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('inventory/scan-lookup', [InventoryController::class, 'scanLookup'])
            ->name('inventory.scan-lookup');

        // 3 endpoint submit fitur Scan (mode Pinjam/Kembalikan/Rusak+Hilang)
        // - tidak perlu didaftarkan sebelum Route::resource (method POST,
        // tidak ada risiko ketangkap {inventory} yang scope-nya GET).
        Route::post('inventory/scan/pinjam', [InventoryController::class, 'scanPinjam'])
            ->name('inventory.scan.pinjam');
        Route::post('inventory/scan/kembalikan', [InventoryController::class, 'scanKembalikan'])
            ->name('inventory.scan.kembalikan');
        Route::post('inventory/scan/status', [InventoryController::class, 'scanStatus'])
            ->name('inventory.scan.status');

        // Sama seperti scan-lookup di atas - didaftarkan SEBELUM Route::resource
        // supaya "mutasi" tidak ketangkap sebagai {inventory} pada route show.
        Route::get('inventory/mutasi', [InventoryMutationController::class, 'index'])
            ->name('inventory.mutasi');

        Route::resource('inventory', InventoryController::class);

        // Route Preview & Export QR Label
        Route::get('inventory/{inventory}/preview-qr', [InventoryController::class, 'previewQr'])
            ->name('inventory.preview-qr');

        Route::get('inventory/{inventory}/export-pdf', [InventoryController::class, 'previewQr'])
            ->name('inventory.export-pdf');

        // FITUR INVENTORY REPORT PDF (Single Report)
        Route::get('inventory/{inventory}/report/preview', [InventoryController::class, 'previewPdf'])
            ->name('inventory.preview');
        Route::get('inventory/{inventory}/report/preview-pdf', [InventoryController::class, 'previewPdf'])
            ->name('inventory.preview-pdf');

        Route::get('inventory/{inventory}/report/download', [InventoryController::class, 'downloadPdf'])
            ->name('inventory.download');
        Route::get('inventory/{inventory}/report/download-pdf', [InventoryController::class, 'downloadPdf'])
            ->name('inventory.download-pdf');

        // FITUR INVENTORY REPORT PDF (Massal / All Report)
        Route::get('inventory/report/preview-all', [InventoryController::class, 'previewAllPdf'])
            ->name('inventory.preview-all');

        // Laporan Massal dibangun bertahap lewat beberapa request AJAX kecil
        // (lihat InventoryService::processAllReportBatch) - supaya aman dari
        // timeout dan tidak butuh queue worker tambahan di hosting manapun.
        Route::post('inventory/report/generate-all/start', [InventoryController::class, 'startAllReportExport'])
            ->name('inventory.generate-all.start');
        Route::post('inventory/report/generate-all/{reportExport}/batch', [InventoryController::class, 'processAllReportBatch'])
            ->name('inventory.generate-all.batch');
        Route::delete('inventory/report/generate-all/{reportExport}/cancel', [InventoryController::class, 'cancelAllReportExport'])
            ->name('inventory.generate-all.cancel');

        // Unduh hasil Laporan Massal yang sudah selesai diproses.
        Route::get('inventory/report/exports/{reportExport}/download', [InventoryController::class, 'downloadQueuedReport'])
            ->name('inventory.download-queued-report');

        // FITUR Kelola Unit Fisik (AJAX per-baris)
        Route::patch('inventory/{inventory}/units/{unit}/status', [InventoryController::class, 'updateUnitStatus'])
            ->name('inventory.units.update-status');

    });

    // Modul Tracking Progress (Project & Task)
    // Route Pipeline SENGAJA didaftarkan SEBELUM Route::resource('projects', ...)
    // di bawah - kalau ditaruh setelah, "pipeline" akan ketangkap sebagai
    // parameter {project} pada route show resource (projects/{project}).
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('projects/pipeline', [ProjectController::class, 'pipeline'])
            ->name('projects.pipeline');

    });

    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::resource('projects', ProjectController::class);

        Route::get('projects/{project}/return-status', [ProjectController::class, 'returnStatus'])
            ->name('projects.return-status');

        Route::put('projects/{project}/crew', [ProjectController::class, 'updateCrew'])
            ->name('projects.crew.update');

        Route::patch('projects/{project}/update-status', [ProjectController::class, 'updateStatus'])
            ->name('projects.update-status');

        Route::post('projects/{project}/lists', [ProjectController::class, 'storeList'])
            ->name('projects.lists.store');

        Route::post('projects/notes', [ProjectNoteController::class, 'store'])
            ->name('projects.notes.store');

        Route::delete('projects/notes/{note}', [ProjectNoteController::class, 'destroy'])
            ->name('projects.notes.destroy');

        Route::resource('tasks', TaskController::class)
            ->only([
                'create',
                'store',
                'show',
                'destroy',
            ]);

        Route::patch(
            'tasks/{task}/update-status',
            [TaskController::class, 'updateStatus']
        )->name('tasks.update-status');

        // Modul Catatan Progress Kerja
        Route::post(
            'tasks/comments',
            [CommentController::class, 'store']
        )->name('tasks.comments.store');

    });

    // Modul Surat Jalan - pembuatan khusus Super Admin sesuai alur yang disepakati
    Route::middleware('role:super_admin')->group(function () {

        Route::get('projects/{project}/surat-jalan/create', [SuratJalanController::class, 'create'])
            ->name('surat-jalan.create');

        Route::post('projects/{project}/surat-jalan', [SuratJalanController::class, 'store'])
            ->name('surat-jalan.store');

    });

    // Modul Data Keuangan - default hanya Super Admin (lihat config/permissions.php
    // -> role_defaults.*.finance), tapi bisa diatur per-user lewat Permission
    // Override di Edit User. Gate role di sini sengaja dibuat luas; otorisasi
    // sesungguhnya ada di ProjectFinanceRequest::authorize() dan
    // FinancialReportController::exportMonthly() lewat hasPermission().
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::put('projects/{project}/finance', [ProjectController::class, 'updateFinance'])
            ->name('projects.finance.update');

        Route::get('reports/finance/monthly', [FinancialReportController::class, 'exportMonthly'])
            ->name('reports.finance.monthly');

    });

    // Modul Surat Jalan - lihat & cetak (Super Admin, Admin, Employee sesuai permission)
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::get('surat-jalan/{suratJalan}', [SuratJalanController::class, 'show'])
            ->name('surat-jalan.show');

        Route::get('surat-jalan/{suratJalan}/preview', [SuratJalanController::class, 'preview'])
            ->name('surat-jalan.preview');

        Route::get('surat-jalan/{suratJalan}/download', [SuratJalanController::class, 'download'])
            ->name('surat-jalan.download');

    });

    // Modul Surat Jalan - kembalikan barang. Role gate dibuat luas; otorisasi
    // sesungguhnya ada di ReturnBarangRequest / ReturnBorrowedUnitsRequest lewat
    // hasPermission('borrowed_items', 'process_return') sehingga bisa diatur
    // per-user lewat Permission Override (termasuk untuk role Employee).
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::post('surat-jalan/items/{item}/return', [SuratJalanController::class, 'returnItem'])
            ->name('surat-jalan.items.return');

        Route::post('barang-pinjaman/{project}/return', [BorrowedItemController::class, 'returnUnits'])
            ->name('borrowed-items.return');

        // Generik (bukan scoped 1 Project) - dipakai tombol Konfirmasi utk
        // grup "Dipinjam oleh: [akun]" di halaman Barang Pinjaman, dan bisa
        // dipakai ulang bebas kalau nanti ada entry point lain yang serupa.
        Route::post('barang-pinjaman/kembalikan', [BorrowedItemController::class, 'returnByIds'])
            ->name('borrowed-items.return-by-ids');
        Route::post('barang-pinjaman/status', [BorrowedItemController::class, 'markStatus'])
            ->name('borrowed-items.mark-status');

    });

    // Modul Barang Pinjaman - lihat (otorisasi sesungguhnya lewat hasPermission
    // borrowed_items.view di BorrowedItemController::index)
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::get('barang-pinjaman', [BorrowedItemController::class, 'index'])
            ->name('borrowed-items.index');

    });

    // Modul Activity Log
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get(
            '/activity-logs',
            [ActivityLogController::class, 'index']
        )->name('activity-logs.index');

    });

    // Modul Activity Log - hapus rentang tanggal (HANYA Super Admin,
    // operasi permanen sehingga dipisah dari group role di atas)
    Route::middleware('role:super_admin')->group(function () {

        Route::delete(
            'activity-logs/delete-range',
            [ActivityLogController::class, 'deleteRange']
        )->name('activity-logs.delete-range');

    });

    // Modul Notifikasi Navbar - endpoint AJAX, di-poll berkala oleh
    // navbar.blade.php. DIBUKA UNTUK SEMUA role (sebelumnya cuma Super
    // Admin/Admin) - isi datanya sendiri sudah otomatis menyesuaikan role
    // di dalam NotificationService (Employee cuma dapat jenis "announcement").
    Route::get('notifications/active', [NotificationController::class, 'active'])
        ->name('notifications.active');

    // Halaman Notifikasi - DIBUKA UNTUK SEMUA role (sebelumnya cuma Super
    // Admin). Isinya menyesuaikan role di dalam AnnouncementController/
    // view-nya sendiri: semua role lihat & bisa kelola (sematkan/hapus)
    // Pengumuman miliknya sendiri; kirim pengumuman baru & kelola jenis
    // notifikasi otomatis tetap HANYA Super Admin (masih di grup terpisah
    // di bawah).
    Route::get('announcements', [AnnouncementController::class, 'index'])
        ->name('announcements.index');

    Route::post('announcements/read-all', [AnnouncementController::class, 'markAllAsRead'])
        ->name('announcements.read-all');

    Route::post('announcements/{id}/read', [AnnouncementController::class, 'markAsRead'])
        ->name('announcements.read');

    Route::post('announcements/{id}/pin', [AnnouncementController::class, 'pin'])
        ->name('announcements.pin');

    Route::post('announcements/{id}/unpin', [AnnouncementController::class, 'unpin'])
        ->name('announcements.unpin');

    Route::delete('announcements/{id}', [AnnouncementController::class, 'destroy'])
        ->name('announcements.destroy');

    // Sematkan/lepas-sematan/hapus 1 notifikasi OTOMATIS (bukan
    // Pengumuman - beda dari 6 route di atas, lihat SystemNotificationController).
    // Digate role:super_admin,admin dulu di sini (siapa saja yang boleh
    // LIHAT 4 jenis notifikasi ini) - untuk aksi hapus, otorisasi lebih
    // ketat lewat hasPermission('notifikasi_sistem','delete') dicek di
    // dalam controller (default hanya Super Admin, Admin butuh Permission
    // Override), jadi 2 lapis: role dulu baru permission.
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::post('system-notifications/{key}/pin', [SystemNotificationController::class, 'pin'])
            ->name('system-notifications.pin');

        Route::post('system-notifications/{key}/unpin', [SystemNotificationController::class, 'unpin'])
            ->name('system-notifications.unpin');

        Route::delete('system-notifications/{key}', [SystemNotificationController::class, 'delete'])
            ->name('system-notifications.destroy');

    });

    // Kirim pengumuman - route dibuka role:super_admin,admin, otorisasi
    // sebenarnya lewat hasPermission('notifikasi_sistem','kirim') di
    // controller (default cuma Super Admin, bisa didelegasikan lewat
    // Permission Override - lihat config/permissions.php).
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::post('announcements', [AnnouncementController::class, 'store'])
            ->name('announcements.store');

    });

    // Kelola jenis notifikasi otomatis - TETAP HANYA Super Admin (belum
    // diminta untuk didaftarkan ke Permission Override, beda dari Kirim
    // Pengumuman di atas).
    Route::middleware('role:super_admin')->group(function () {

        Route::post('notification-settings', [NotificationSettingController::class, 'update'])
            ->name('notification-settings.update');

    });

    // Push subscription (Web Push browser) - SEMUA role yang login boleh
    // mendaftarkan browsernya sendiri untuk menerima pengumuman, bukan
    // cuma Super Admin (Super Admin yang MENGIRIM, siapa saja yang login
    // bisa jadi PENERIMA).
    Route::post('push-subscription', [AnnouncementController::class, 'subscribe'])
        ->name('push-subscription.store');

    Route::delete('push-subscription', [AnnouncementController::class, 'unsubscribe'])
        ->name('push-subscription.destroy');

    // Modul Trash - route dibuka role:super_admin,admin untuk SEMUA aksi
    // (termasuk hapus permanen) - otorisasi sebenarnya sekarang lewat
    // hasPermission('trash', ...) di controller (lihat config/permissions.php),
    // bukan lagi di-hardcode di sini, supaya toggle Permission Override
    // beneran ngaruh (dulu forceDelete tetap terkunci Super Admin di sini
    // walau ada toggle-nya, jadi toggle-nya percuma).
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('/trash', [TrashController::class, 'index'])
            ->name('trash.index');

        Route::patch('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])
            ->whereIn('type', ['project', 'task', 'inventory', 'folder', 'file'])
            ->whereNumber('id')
            ->name('trash.restore');

        Route::delete('/trash/{type}/{id}', [TrashController::class, 'forceDelete'])
            ->whereIn('type', ['project', 'task', 'inventory', 'folder', 'file'])
            ->whereNumber('id')
            ->name('trash.force-delete');

    });

    // Modul Data Integration
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::prefix('data-integration')->group(function () {

            // 1. Folder Management
            Route::get('/folder-management', [FolderController::class, 'index'])
                ->name('folders.index');

            Route::get('/folder-management/{folder}', [FolderController::class, 'show'])
                ->name('folders.show');

            Route::post('/folder-management/store', [FolderController::class, 'store'])
                ->name('folders.store');

            Route::patch('/folders/{folder}/rename', [FolderController::class, 'rename'])
                ->name('folders.rename');

            Route::patch('/folders/{folder}/move', [FolderController::class, 'move'])
                ->name('folders.move');

            Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])
                ->name('folders.destroy');

            // 2. My Files
            Route::get('/my-files', [FileController::class, 'myFiles'])
                ->name('files.my-files');

            Route::post('/files/store', [FileController::class, 'store'])
                ->name('files.store');

            Route::get('/files/{file}/download', [FileController::class, 'download'])
                ->name('files.download');

            Route::patch('/files/{file}/rename', [FileController::class, 'rename'])
                ->name('files.rename');

            Route::patch('/files/{file}/move', [FileController::class, 'move'])
                ->name('files.move');

            Route::delete('/files/{file}', [FileController::class, 'destroy'])
                ->name('files.destroy');

        });

    });

    // Modul Kontak (buku alamat client yang pernah memakai jasa) - berdiri
    // sendiri (tidak wajib terhubung ke Project - link ke Project cuma
    // terisi kalau field Client dipilih dari saran autocomplete). Akses
    // sama seperti Project: Super Admin, Admin, Employee.
    //
    // Route search SENGAJA didaftarkan SEBELUM Route::resource di bawah -
    // kalau ditaruh setelah, "search" akan ketangkap sebagai parameter
    // {contact} pada route show resource (contacts/{contact}).
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::get('contacts/search', [ContactController::class, 'search'])
            ->name('contacts.search');

        Route::resource('contacts', ContactController::class);

    });

    // Modul User Management
    //
    // 1) Create/store didaftarkan lebih dulu (sebelum show/{user}) supaya
    //    '/users/create' tidak tertangkap wildcard {user}. Tetap Super Admin
    //    saja - lihat catatan keamanan di grup ke-3.
    Route::middleware('role:super_admin')->group(function () {

        Route::get('users/create', [UserController::class, 'create'])
            ->name('users.create');

        Route::post('users', [UserController::class, 'store'])
            ->name('users.store');

    });

    // 2) Lihat daftar & detail user - BISA didelegasikan lewat Permission
    //    Override (user_management.view_user), makanya role gate dibuat
    //    lebih luas; otorisasi sesungguhnya lewat hasPermission() di
    //    UserController::index()/show().
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');

        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show');

    });

    // 3) Edit & Hapus user - BISA didelegasikan lewat Permission Override
    //    (user_management.edit_user / delete_user). CATATAN KEAMANAN: kalau
    //    Admin diberi edit_user, field Role & Hak Akses (permission_overrides)
    //    pada form Edit tetap diabaikan kalau bukan Super Admin yang mengirim
    //    (lihat UserService::updateUser) - jadi Admin hanya bisa mengubah
    //    nama/email/status/password user lain, tidak bisa menaikkan role
    //    atau memberi hak akses lebih lewat form ini.
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit');

        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
            ->name('users.update');

        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');

    });

    // 4) Reset password, ubah role, ubah status - SELALU Super Admin, TIDAK
    //    bisa didelegasikan lewat Permission Override (reset password = ambil
    //    alih akun orang lain; ubah role = jalur lain untuk eskalasi privilege).
    Route::middleware('role:super_admin')->group(function () {

        Route::patch(
            'users/{user}/status',
            [UserController::class, 'changeStatus']
        )->name('users.change-status');

        Route::patch(
            'users/{user}/role',
            [UserController::class, 'changeRole']
        )->name('users.change-role');

        Route::patch(
            'users/{user}/reset-password',
            [UserController::class, 'resetPassword']
        )->name('users.reset-password');

    });

});

<?php

use App\Http\Controllers\ActivityLog\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\DataIntegration\FileController;
use App\Http\Controllers\DataIntegration\FolderController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\ProjectNoteController;
use App\Http\Controllers\Report\FinancialReportController;
use App\Http\Controllers\Project\SuratJalanController;
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

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

});

// Group rute terproteksi (Auth) - Harus Login Terlebih Dahulu
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Modul Inventory Management
    Route::middleware('role:super_admin,admin')->group(function () {

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

        Route::get('inventory/report/download-all', [InventoryController::class, 'downloadAllPdf'])
            ->name('inventory.download-all');
        Route::get('inventory/report/download-all-pdf', [InventoryController::class, 'downloadAllPdf'])
            ->name('inventory.download-all-pdf');

        // FITUR Kelola Unit Fisik (AJAX per-baris)
        Route::patch('inventory/{inventory}/units/{unit}/status', [InventoryController::class, 'updateUnitStatus'])
            ->name('inventory.units.update-status');

    });

    // Modul Tracking Progress (Project & Task)
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

    // Modul Notifikasi Navbar (Super Admin & Admin) - endpoint AJAX, di-poll
    // berkala oleh navbar.blade.php.
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('notifications/active', [NotificationController::class, 'active'])
            ->name('notifications.active');

    });

    // Modul Trash - lihat & pulihkan (Super Admin & Admin)
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get('/trash', [TrashController::class, 'index'])
            ->name('trash.index');

        Route::patch('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])
            ->whereIn('type', ['project', 'task', 'inventory', 'folder', 'file'])
            ->whereNumber('id')
            ->name('trash.restore');

    });

    // Modul Trash - hapus permanen (HANYA Super Admin)
    Route::middleware('role:super_admin')->group(function () {

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

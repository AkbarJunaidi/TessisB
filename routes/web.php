<?php

use App\Http\Controllers\ActivityLog\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\DataIntegration\FileController;
use App\Http\Controllers\DataIntegration\FolderController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Task\CommentController;
use App\Http\Controllers\Task\TaskController;
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

// Route Preview QR Label
        Route::get('inventory/{inventory}/preview-qr', [InventoryController::class, 'previewQr'])
            ->name('inventory.preview-qr');

// label qr pdf
        Route::get('inventory/{inventory}/export-pdf', [InventoryController::class, 'exportPdf'])
            ->name('inventory.export-pdf');


        // FITUR INVENTORY REPORT PDF
        Route::get('inventory/{inventory}/report/preview', [InventoryController::class, 'previewPdf'])
            ->name('inventory.preview');

        Route::get('inventory/{inventory}/report/download', [InventoryController::class, 'downloadPdf'])
            ->name('inventory.download');

        Route::get('inventory/report/preview-all', [InventoryController::class, 'previewAllPdf'])
            ->name('inventory.preview-all');

        Route::get('inventory/report/download-all', [InventoryController::class, 'downloadAllPdf'])
            ->name('inventory.download-all');

    });

    // Modul Tracking Progress (Project & Task)
    Route::middleware('role:super_admin,admin,employee')->group(function () {

        Route::resource('projects', ProjectController::class);

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

    // Modul Activity Log
    Route::middleware('role:super_admin,admin')->group(function () {

        Route::get(
            '/activity-logs',
            [ActivityLogController::class, 'index']
        )->name('activity-logs.index');

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
    Route::middleware('role:super_admin')->group(function () {

        Route::resource('users', UserController::class);

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

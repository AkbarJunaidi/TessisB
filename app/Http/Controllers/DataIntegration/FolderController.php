<?php

namespace App\Http\Controllers\DataIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataIntegration\FolderRequest;
use App\Services\DataIntegration\FolderService;
use App\Models\Folder;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class FolderController extends Controller
{
    protected FolderService $folderService;

    /**
     * Dependency Injection melalui Constructor
     */
    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Menampilkan root folder utama pada Folder Management.
     */
    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Integrasi Data.'
        );

        // Mengambil folder tingkat paling atas (root) yang tidak memiliki parent
        $folders = Folder::with('user')
            ->whereNull('parent_id')
            ->get();

        /**
         * ATURAN FILE BERDASARKAN DOKUMEN SISTEM:
         * 1. Jika diupload dari Folder Management (Masuk ruang bersama), file WAJIB memiliki folder_id.
         * 2. Jika diupload dari My Files (Masuk ruang pribadi), file memiliki folder_id = null.
         * Maka pada halaman utama Folder Management root, file yang muncul di luar folder adalah 0 (kosong).
         * File hanya akan muncul di dalam folder tempat ia diunggah.
         */
        $files = collect();

        return view('data-integration.folder-management', [
            'folders' => $folders,
            'files' => $files,
            'current_folder' => null
        ]);
    }

    /**
     * Membuka sub-folder tertentu (Open Folder).
     */
    public function show(Folder $folder): View
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Integrasi Data.'
        );

        // Ambil anak folder (sub-folder) langsung di bawah folder aktif saat ini
        $subFolders = Folder::with('user')
            ->where('parent_id', $folder->id)
            ->get();

        // Ambil seluruh berkas bersama yang diunggah ke dalam folder ini
        $files = File::with('user')
            ->where('folder_id', $folder->id)
            ->get();

        return view('data-integration.folder-management', [
            'folders' => $subFolders,
            'files' => $files,
            'current_folder' => $folder
        ]);
    }

    /**
     * Membuat folder baru (Bersama atau Sub-folder).
     */
    public function store(FolderRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $this->folderService->createFolder($validated);

            return redirect()->back()->with('success', 'Folder berhasil dibuat secara aman.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ganti Nama Folder
     */
    public function rename(Folder $folder, \Illuminate\Http\Request $request): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'rename'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah nama folder.'
        );

        $request->validate(['name' => 'required|string|max:255']);
        $this->folderService->renameFolder($folder, $request->name);
        return redirect()->back()->with('success', 'Folder berhasil diubah namanya.');
    }

    /**
     * Pindahkan Folder
     */
    public function move(Folder $folder, \Illuminate\Http\Request $request): RedirectResponse
    {
        // 'move' belum punya action khusus di katalog permission - disamakan
        // dengan 'rename' karena sama-sama aksi reorganisasi folder.
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'rename'),
            403,
            'Anda tidak memiliki hak akses untuk memindahkan folder.'
        );

        try {
            $targetId = $request->input('target_folder_id') ?: null;
            $this->folderService->moveFolder($folder, $targetId);
            return redirect()->back()->with('success', 'Folder berhasil dipindahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Hapus Folder
     */
    public function destroy(Folder $folder): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'delete'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus folder.'
        );

        $this->folderService->deleteFolder($folder);
        return redirect()->back()->with('success', 'Folder dan seluruh isinya berhasil dihapus.');
    }

}

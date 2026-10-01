<?php

namespace App\Http\Controllers\DataIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataIntegration\FolderRequest;
use App\Services\DataIntegration\FolderService;
use App\Services\DataIntegration\LockService;
use App\Models\Folder;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

class FolderController extends Controller
{
    protected FolderService $folderService;

    protected LockService $lockService;

    /**
     * Dependency Injection melalui Constructor
     */
    public function __construct(FolderService $folderService, LockService $lockService)
    {
        $this->folderService = $folderService;
        $this->lockService   = $lockService;
    }

    /**
     * Menampilkan root folder utama pada Folder Management (hanya folder bersama).
     */
    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Integrasi Data.'
        );

        // Folder tingkat paling atas (root) di ruang bersama. Folder pribadi (My Files) tidak ikut.
        $folders = Folder::with(['user', 'lockedBy'])
            ->whereNull('parent_id')
            ->where('is_private', false)
            ->orderBy('name')
            ->get();

        /**
         * ATURAN FILE BERDASARKAN DOKUMEN SISTEM:
         * 1. Jika diupload dari Folder Management (Masuk ruang bersama), file WAJIB memiliki folder_id.
         * 2. Jika diupload dari My Files (Masuk ruang pribadi), file tanpa folder atau di folder pribadi.
         * Maka pada halaman utama Folder Management root, file yang muncul di luar folder adalah 0 (kosong).
         * File hanya akan muncul di dalam folder tempat ia diunggah.
         */
        $files = collect();

        return view('data-integration.folder-management', [
            'folders'        => $folders,
            'files'          => $files,
            'current_folder' => null,
            'moveGroups'     => $this->sharedMoveGroups(),
            'systemFolderIds' => $this->folderService->systemFolderIds(),
            'currentLock'      => null,
            'lockedContentIds' => $this->lockService->folderIdsWithLockedContents(),
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

        // Folder pribadi hanya dibuka lewat My Files, bukan Folder Management.
        abort_if($folder->is_private, 404);

        // Ambil anak folder (sub-folder) langsung di bawah folder aktif saat ini
        $subFolders = Folder::with(['user', 'lockedBy'])
            ->where('parent_id', $folder->id)
            ->where('is_private', false)
            ->orderBy('name')
            ->get();

        // Ambil seluruh berkas bersama yang diunggah ke dalam folder ini
        $files = File::with(['user', 'lockedBy'])
            ->where('folder_id', $folder->id)
            ->orderBy('file_name')
            ->get();

        return view('data-integration.folder-management', [
            'folders'        => $subFolders,
            'files'          => $files,
            'current_folder' => $folder,
            'moveGroups'     => $this->sharedMoveGroups(),
            'systemFolderIds' => $this->folderService->systemFolderIds(),
            // Kunci yang berlaku untuk folder yang sedang dibuka (miliknya atau folder induknya);
            // bila ada, seluruh isinya terlindungi.
            'currentLock'      => $this->lockService->effectiveLock($folder),
            'lockedContentIds' => $this->lockService->folderIdsWithLockedContents(),
        ]);
    }

    /**
     * Membuat folder baru: folder bersama (dari Folder Management) atau, bila field
     * space=private, folder pribadi (dari My Files).
     */
    public function store(FolderRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->folderService->createFolder([
                'name'      => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'private'   => ($validated['space'] ?? null) === 'private',
            ]);

            return redirect()->back()->with('success', 'Folder berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ganti Nama Folder
     */
    public function rename(Folder $folder, Request $request): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'rename'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah nama folder.'
        );

        abort_unless(
            $this->folderService->canModify($folder),
            403,
            'Folder pribadi hanya dapat diubah oleh pemiliknya.'
        );

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:' . FolderRequest::NAME_REGEX],
        ], [
            'name.required' => 'Nama folder wajib diisi.',
            'name.regex'    => 'Nama folder tidak boleh mengandung karakter \\ / ? % * : | " < >.',
        ]);

        try {
            $this->folderService->renameFolder($folder, $request->input('name'));

            return redirect()->back()->with('success', 'Folder berhasil diubah namanya.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pindahkan Folder
     */
    public function move(Folder $folder, Request $request): RedirectResponse
    {
        // 'move' belum punya action khusus di katalog permission - disamakan
        // dengan 'rename' karena sama-sama aksi reorganisasi folder.
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'rename'),
            403,
            'Anda tidak memiliki hak akses untuk memindahkan folder.'
        );

        abort_unless(
            $this->folderService->canModify($folder),
            403,
            'Folder pribadi hanya dapat dipindahkan oleh pemiliknya.'
        );

        $request->validate([
            'target_folder_id' => ['nullable', 'exists:folders,id'],
        ], [
            'target_folder_id.exists' => 'Folder tujuan tidak ditemukan.',
        ]);

        try {
            $targetId = $request->input('target_folder_id') ?: null;
            $this->folderService->moveFolder($folder, $targetId ? (int) $targetId : null);

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

        abort_unless(
            $this->folderService->canModify($folder),
            403,
            'Folder pribadi hanya dapat dihapus oleh pemiliknya.'
        );

        try {
            $this->folderService->deleteFolder($folder);

            return redirect()->back()->with('success', 'Folder dan seluruh isinya berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kunci folder bersama (gembok).
     */
    public function lock(Folder $folder): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'lock'),
            403,
            'Anda tidak memiliki hak akses untuk mengunci folder.'
        );

        try {
            $this->folderService->lockFolder($folder);

            return redirect()->back()->with('success', 'Folder dikunci.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Buka kunci folder.
     */
    public function unlock(Folder $folder): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'lock'),
            403,
            'Anda tidak memiliki hak akses untuk membuka kunci folder.'
        );

        try {
            $this->folderService->unlockFolder($folder);

            return redirect()->back()->with('success', 'Kunci folder dibuka.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Daftar tujuan pindah untuk halaman Folder Management (semua folder bersama, berikut path-nya).
     * Bentuk: [['label' => ..., 'warn' => bool, 'options' => [id => path]]].
     */
    private function sharedMoveGroups(): array
    {
        return [
            [
                'label'   => 'Folder bersama',
                'warn'    => false,
                'options' => $this->folderService->pathMap(false),
            ],
        ];
    }

}

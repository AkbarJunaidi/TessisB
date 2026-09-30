<?php

namespace App\Http\Controllers\DataIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataIntegration\FileRequest;
use App\Models\File;
use App\Services\DataIntegration\FileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class FileController extends Controller
{
    /**
     * Service File.
     */
    protected FileService $fileService;

    /**
     * Constructor.
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Menampilkan halaman My Files.
     *
     * tab=saya (default) : file PRIBADI user ini (tanpa folder).
     * tab=dibagikan      : ringkasan file yang diunggah/digenerate dan folder yang dibuat
     *                      user ini di ruang bersama. Filter opsional:
     *                      jenis=folder|file, sumber=upload|generate.
     */
    public function myFiles(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat Integrasi Data.'
        );

        $tab    = $request->query('tab') === 'dibagikan' ? 'dibagikan' : 'saya';
        $jenis  = in_array($request->query('jenis'), ['folder', 'file'], true) ? $request->query('jenis') : null;
        $sumber = in_array($request->query('sumber'), ['upload', 'generate'], true) ? $request->query('sumber') : null;

        $files = collect();
        $items = collect();

        if ($tab === 'dibagikan') {
            $items = $this->fileService->getSharedActivity($jenis, $sumber);
        } else {
            $files = $this->fileService->getPrivateFiles();
        }

        return view(
            'data-integration.my-files',
            compact('tab', 'files', 'items', 'jenis', 'sumber')
        );
    }

    /**
     * Upload file.
     */
    public function store(
        FileRequest $request
    ): RedirectResponse {

        try {

            $this->fileService->uploadFile(
                $request->file('file'),
                $request->folder_id
            );

            return back()->with(
                'success',
                'Berkas berhasil diunggah.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Download file.
     */
    public function download(
        File $file
    ): BinaryFileResponse|RedirectResponse {

        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'download'),
            403,
            'Anda tidak memiliki hak akses untuk mendownload file.'
        );

        abort_unless(
            $this->fileService->canAccess($file),
            403,
            'Berkas ini bersifat pribadi milik pengguna lain.'
        );

        try {

            return $this->fileService->downloadFile($file);

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Preview file (inline) - PDF, gambar, dan teks.
     * Dipakai oleh modal preview (iframe / img / fetch), jadi kegagalan
     * dibalas teks polos + status HTTP, bukan redirect.
     */
    public function preview(File $file): BinaryFileResponse|Response
    {
        // Preview memperlihatkan isi file penuh, jadi disamakan dengan hak
        // membaca + mengunduh (browser memang membolehkan simpan dari preview).
        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'view')
                && Auth::user()?->hasPermission('data_integration', 'download'),
            403,
            'Anda tidak memiliki hak akses untuk melihat isi file.'
        );

        abort_unless(
            $this->fileService->canAccess($file),
            403,
            'Berkas ini bersifat pribadi milik pengguna lain.'
        );

        try {

            return $this->fileService->previewFile($file);

        } catch (Exception $e) {

            return response(
                $e->getMessage(),
                422,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }
    }

    /**
     * Rename file.
     */
    public function rename(
        FileRequest $request,
        File $file
    ): RedirectResponse {

        abort_unless(
            $this->fileService->canModify($file),
            403,
            'File pribadi hanya dapat diubah oleh pemiliknya.'
        );

        try {

            $this->fileService->renameFile(
                $file,
                $request->file_name
            );

            return back()->with(
                'success',
                'Nama file berhasil diperbarui.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Move file.
     */
    public function move(
        FileRequest $request,
        File $file
    ): RedirectResponse {

        abort_unless(
            $this->fileService->canModify($file),
            403,
            'File pribadi hanya dapat dipindahkan oleh pemiliknya.'
        );

        try {

            $this->fileService->moveFile(
                $file,
                $request->target_folder_id
            );

            return back()->with(
                'success',
                'File berhasil dipindahkan.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Delete file.
     */
    public function destroy(
        File $file
    ): RedirectResponse {

        abort_unless(
            Auth::user()?->hasPermission('data_integration', 'delete'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus file.'
        );

        abort_unless(
            $this->fileService->canModify($file),
            403,
            'File pribadi hanya dapat dihapus oleh pemiliknya.'
        );

        try {

            $this->fileService->deleteFile($file);

            return back()->with(
                'success',
                'File berhasil dihapus.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}

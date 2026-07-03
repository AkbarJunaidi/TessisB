<?php

namespace App\Http\Controllers\DataIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataIntegration\FileRequest;
use App\Models\File;
use App\Services\DataIntegration\FileService;
use Illuminate\Http\RedirectResponse;
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
     */
    public function myFiles(): View
    {
        $files = $this->fileService->getMyFiles();

        return view(
            'data-integration.my-files',
            compact('files')
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
     * Rename file.
     */
    public function rename(
        FileRequest $request,
        File $file
    ): RedirectResponse {

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

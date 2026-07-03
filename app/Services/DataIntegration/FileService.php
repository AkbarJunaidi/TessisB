<?php

namespace App\Services\DataIntegration;

use App\Models\File;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\Collection;

class FileService
{
    /**
     * Service Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Constructor.
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Mengambil seluruh file milik user yang sedang login.
     */
    public function getMyFiles(): Collection
    {
        return File::where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    /**
     * Upload file.
     */
    public function uploadFile(
        UploadedFile $uploadedFile,
        ?int $folderId = null
    ): File {

        try {

            $originalName = $uploadedFile->getClientOriginalName();

            $fileName = time() . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();

            $path = $uploadedFile->storeAs(
                'uploads/data_integration',
                $fileName,
                'public'
            );

            $file = File::create([
                'folder_id' => $folderId,
                'user_id' => Auth::id(),
                'file_name' => $originalName,
                'file_path' => $path,
                'file_size' => $uploadedFile->getSize(),
                'file_type' => $uploadedFile->getClientOriginalExtension(),
            ]);

            $this->activityLogService->log(
                Auth::id(),
                'Integrasi Data',
                'Upload File'
            );

            return $file;

        } catch (Exception $e) {
            throw new Exception(
                'Gagal mengunggah file: ' . $e->getMessage()
            );
        }
    }

    /**
     * Download file.
     */
    public function downloadFile(File $file): BinaryFileResponse
    {
        if (!Storage::disk('public')->exists($file->file_path)) {
            throw new Exception('Berkas tidak ditemukan.');
        }

        $this->activityLogService->log(
            Auth::id(),
            'Integrasi Data',
            'Download File'
        );

        return response()->download(
            storage_path('app/public/' . $file->file_path),
            $file->file_name
        );
    }

    /**
     * Rename file.
     */
    public function renameFile(
        File $file,
        string $newFileName
    ): bool {

        try {

            return $file->update([
                'file_name' => $newFileName
            ]);

        } catch (Exception $e) {
            throw new Exception(
                'Gagal mengubah nama file: ' . $e->getMessage()
            );
        }
    }

    /**
     * Move file.
     */
    public function moveFile(
        File $file,
        ?int $targetFolderId
    ): bool {

        try {

            return $file->update([
                'folder_id' => $targetFolderId
            ]);

        } catch (Exception $e) {
            throw new Exception(
                'Gagal memindahkan file: ' . $e->getMessage()
            );
        }
    }

    /**
     * Delete file (Soft Delete).
     */
    public function deleteFile(File $file): ?bool
    {
        try {

            $deleted = $file->delete();

            if ($deleted) {

                $this->activityLogService->log(
                    Auth::id(),
                    'Integrasi Data',
                    'Delete File'
                );
            }

            return $deleted;

        } catch (Exception $e) {
            throw new Exception(
                'Gagal menghapus file: ' . $e->getMessage()
            );
        }
    }
}

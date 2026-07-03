<?php

namespace App\Services\DataIntegration;

use App\Models\Folder;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Support\Facades\Auth;

class FolderService
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
     * Membuat folder baru.
     */
    public function createFolder(array $data): Folder
    {
        try {

            $folder = Folder::create([
                'name'       => $data['name'],
                'parent_id'  => $data['parent_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->activityLogService->log(
                Auth::id(),
                'Integrasi Data',
                'Create Folder'
            );

            return $folder;

        } catch (Exception $e) {
            throw new Exception(
                'Gagal membuat folder: ' . $e->getMessage()
            );
        }
    }

    /**
     * Rename folder.
     */
    public function renameFolder(
        Folder $folder,
        string $newName
    ): bool {

        return $folder->update([
            'name' => $newName
        ]);
    }

    /**
     * Memindahkan folder.
     */
    public function moveFolder(
        Folder $folder,
        ?int $targetFolderId
    ): bool {

        if ($targetFolderId === $folder->id) {
            throw new Exception(
                'Folder tidak dapat dipindahkan ke dalam dirinya sendiri.'
            );
        }

        return $folder->update([
            'parent_id' => $targetFolderId
        ]);
    }

    /**
     * Menghapus folder (Soft Delete).
     */
    public function deleteFolder(
        Folder $folder
    ): ?bool {

        $deleted = $folder->delete();

        if ($deleted) {

            $this->activityLogService->log(
                Auth::id(),
                'Integrasi Data',
                'Delete Folder'
            );
        }

        return $deleted;
    }
}

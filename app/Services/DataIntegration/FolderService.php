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

    /**
     * Mengambil folder khusus sebuah project (Document Center), atau membuatnya
     * otomatis jika belum ada. Struktur: root "Projects" > "<Nama Project>".
     * Dipanggil otomatis saat project baru dibuat (lihat ProjectService::createProject).
     */
    public function getOrCreateProjectFolder(\App\Models\Project $project): Folder
    {
        if ($project->folder) {
            return $project->folder;
        }

        $rootFolder = Folder::whereNull('parent_id')
            ->whereNull('project_id')
            ->where('name', 'Projects')
            ->first();

        if (!$rootFolder) {
            $rootFolder = Folder::create([
                'name'       => 'Projects',
                'parent_id'  => null,
                'created_by' => Auth::id(),
            ]);
        }

        return Folder::create([
            'name'       => $project->name,
            'parent_id'  => $rootFolder->id,
            'project_id' => $project->id,
            'created_by' => Auth::id(),
        ]);
    }
}

<?php

namespace App\Services\DataIntegration;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Database\Eloquent\Collection;

class FileService
{
    /**
     * Service Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Service Folder (peta path & aturan folder pribadi/bersama).
     */
    protected FolderService $folderService;

    /**
     * Constructor.
     */
    public function __construct(
        ActivityLogService $activityLogService,
        FolderService $folderService
    ) {
        $this->activityLogService = $activityLogService;
        $this->folderService      = $folderService;
    }

    /**
     * Batas ukuran file teks yang boleh dipratinjau (supaya browser tidak berat).
     */
    private const PREVIEW_TEXT_MAX_BYTES = 1048576; // 1 MB

    /**
     * Tab "File saya": file PRIBADI user yang sedang login di dalam satu folder pribadi
     * ($folderId) atau di akar My Files ($folderId = null).
     */
    public function getPrivateFiles(?int $folderId = null): Collection
    {
        return File::where('user_id', Auth::id())
            ->when(
                $folderId,
                fn ($q) => $q->where('folder_id', $folderId),
                fn ($q) => $q->whereNull('folder_id')
            )
            ->orderBy('file_name')
            ->get();
    }

    /**
     * Tab "Dibagikan": ringkasan aktivitas user yang sedang login di ruang bersama
     * (Folder Management) - file yang ia unggah, PDF yang ia generate, dan folder
     * yang ia buat - lengkap dengan lokasinya. BUKAN fitur berbagi antar user: setiap
     * user hanya melihat miliknya sendiri, jadi isinya berbeda untuk tiap user.
     *
     * @param  string|null $type    null (semua) | 'folder' | 'file'
     * @param  string|null $source  null (semua) | 'upload' | 'generate'
     * @return \Illuminate\Support\Collection<int, array>  urut terbaru; tiap item array: kind, model, name,
     *                                 location, location_url, source_label, date, group
     */
    public function getSharedActivity(?string $type = null, ?string $source = null): \Illuminate\Support\Collection
    {
        $userId = Auth::id();
        $paths  = $this->folderService->pathMap(false);
        $items  = collect();

        // Filter "Sumber" hanya berlaku untuk file; folder tidak punya asal upload/generate.
        $includeFolders = $type !== 'file' && !in_array($source, ['upload', 'generate'], true);
        $includeFiles   = $type !== 'folder';

        if ($includeFiles) {
            $files = File::where('user_id', $userId)
                ->whereNotNull('folder_id')
                // Buang file di dalam folder pribadi (termasuk yang folder-nya sudah dihapus).
                ->whereDoesntHave('folder', fn ($q) => $q->withTrashed()->where('is_private', true))
                ->when($source === 'upload', fn ($q) => $q->where('file_path', 'like', File::UPLOAD_DIR . '/%'))
                ->when($source === 'generate', fn ($q) => $q->where('file_path', 'not like', File::UPLOAD_DIR . '/%'))
                ->get();

            foreach ($files as $file) {
                $known = isset($paths[$file->folder_id]);

                $items->push([
                    'kind'         => 'file',
                    'model'        => $file,
                    'name'         => $file->file_name,
                    'location'     => $known ? $paths[$file->folder_id] : 'Folder dihapus',
                    'location_url' => $known ? route('folders.show', $file->folder_id) : null,
                    'source_label' => $file->source_label,
                    'date'         => $file->created_at,
                ]);
            }
        }

        if ($includeFolders) {
            $folders = Folder::where('created_by', $userId)->where('is_private', false)->get();

            foreach ($folders as $folder) {
                $parentKnown = $folder->parent_id && isset($paths[$folder->parent_id]);

                $items->push([
                    'kind'         => 'folder',
                    'model'        => $folder,
                    'name'         => $folder->name,
                    // Folder tingkat atas berada di akar Folder Management.
                    'location'     => $parentKnown ? $paths[$folder->parent_id] : 'Folder Management',
                    'location_url' => $parentKnown
                        ? route('folders.show', $folder->parent_id)
                        : route('folders.index'),
                    'source_label' => $folder->project_id ? 'Folder project' : 'Folder dibuat',
                    'date'         => $folder->created_at,
                ]);
            }
        }

        return $items
            ->sortByDesc(fn (array $item) => $item['date'])
            ->map(function (array $item) {
                $item['group'] = $this->dateGroup($item['date']);
                return $item;
            })
            ->values();
    }

    /**
     * Kelompok tanggal untuk judul bagian di daftar (gaya Drive).
     */
    private function dateGroup($date): string
    {
        if ($date->gte(now()->startOfDay())) {
            return 'Hari ini';
        }

        if ($date->gte(now()->subDays(7)->startOfDay())) {
            return '7 hari terakhir';
        }

        if ($date->gte(now()->subDays(30)->startOfDay())) {
            return '30 hari terakhir';
        }

        return 'Lebih lama';
    }

    /**
     * Boleh MEMBACA (preview/unduh) file ini?
     * - File di folder bersama : cukup permission modul (dicek di controller).
     * - File pribadi           : hanya pemiliknya.
     */
    public function canAccess(File $file, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (!$user) {
            return false;
        }

        return !$file->isPrivate() || (int) $file->user_id === (int) $user->id;
    }

    /**
     * Boleh MENGUBAH (rename/pindah/hapus) file ini?
     * File pribadi hanya boleh diubah pemiliknya; file di folder bersama
     * mengikuti permission modul.
     */
    public function canModify(File $file, ?User $user = null): bool
    {
        // Aturannya sama dengan canAccess untuk saat ini.
        return $this->canAccess($file, $user);
    }

    /**
     * Upload file.
     */
    public function uploadFile(
        UploadedFile $uploadedFile,
        ?int $folderId = null
    ): File {

        try {

            $this->assertUploadTargetAllowed($folderId);

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
     * Mendaftarkan/memperbarui entri File untuk berkas yang SUDAH tersimpan di disk
     * (misalnya PDF hasil generate seperti Surat Jalan), bukan hasil upload multipart.
     * updateOrCreate berdasarkan folder_id + file_path agar generate ulang (mis. print
     * ulang setelah data berubah) tidak membuat duplikat entri di Document Center.
     */
    public function registerGeneratedFile(
        int $folderId,
        string $storedPath,
        string $displayName,
        int $fileSize,
        string $fileType = 'pdf'
    ): File {

        return File::updateOrCreate(
            [
                'folder_id' => $folderId,
                'file_path' => $storedPath,
            ],
            [
                'user_id'   => Auth::id(),
                'file_name' => $displayName,
                'file_size' => $fileSize,
                'file_type' => $fileType,
            ]
        );
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
     * Sajikan file untuk dipratinjau (inline) di browser.
     * Hanya PDF, gambar, dan teks - jenis lain ditolak dan diarahkan ke unduh.
     *
     * @throws Exception
     */
    public function previewFile(File $file): BinaryFileResponse
    {
        $mime = $file->preview_mime;

        if (!$mime) {
            throw new Exception('Jenis file ini belum bisa dipratinjau. Silakan unduh.');
        }

        if (!Storage::disk('public')->exists($file->file_path)) {
            throw new Exception('Berkas tidak ditemukan.');
        }

        if ($file->preview_type === 'text' && (int) $file->file_size > self::PREVIEW_TEXT_MAX_BYTES) {
            throw new Exception('File teks terlalu besar untuk dipratinjau (maks. 1 MB). Silakan unduh.');
        }

        $response = response()->file(
            Storage::disk('public')->path($file->file_path),
            [
                'Content-Type'           => $mime,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control'          => 'private, max-age=0, must-revalidate',
            ]
        );

        // Nama file di header: versi ASCII untuk fallback, versi asli untuk browser modern.
        $fallback = str_replace(['%', '/', '\\', '"'], '_', Str::ascii($file->file_name));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->file_name,
            $fallback !== '' ? $fallback : 'file'
        );

        return $response;
    }

    /**
     * Rename file.
     */
    public function renameFile(
        File $file,
        string $newFileName
    ): bool {

        try {

            // Ekstensi asli dikunci: "laporan" -> "laporan.docx".
            return $file->update([
                'file_name' => $file->applyLockedExtension($newFileName)
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

            $this->assertMoveAllowed($file, $targetFolderId);

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
     * Aturan pindah file:
     * - File pribadi : boleh ke akar My Files (target kosong), ke folder pribadi milik sendiri,
     *                  atau ke folder bersama (file menjadi terlihat oleh yang punya akses).
     * - File bersama : hanya ke folder bersama lain. Tidak boleh ke akar (file bersama wajib
     *                  berada di sebuah folder) dan tidak boleh kembali ke ruang pribadi.
     *
     * @throws Exception
     */
    private function assertMoveAllowed(File $file, ?int $targetFolderId): void
    {
        $target = null;

        if ($targetFolderId !== null) {
            $target = Folder::find($targetFolderId);

            if (!$target) {
                throw new Exception('Folder tujuan tidak ditemukan.');
            }

            if ($target->is_private && (int) $target->created_by !== (int) Auth::id()) {
                throw new Exception('Folder tujuan bersifat pribadi milik pengguna lain.');
            }
        }

        if (!$file->isPrivate() && (!$target || $target->is_private)) {
            throw new Exception('File di ruang bersama hanya dapat dipindahkan ke folder bersama lain.');
        }
    }

    /**
     * Folder tujuan upload harus ada dan, bila pribadi, milik user yang login.
     *
     * @throws Exception
     */
    private function assertUploadTargetAllowed(?int $folderId): void
    {
        if ($folderId === null) {
            return;
        }

        $folder = Folder::find($folderId);

        if (!$folder) {
            throw new Exception('Folder tujuan tidak ditemukan.');
        }

        if ($folder->is_private && (int) $folder->created_by !== (int) Auth::id()) {
            throw new Exception('Folder tujuan bersifat pribadi milik pengguna lain.');
        }
    }

    /**
     * Delete file (Soft Delete).
     */
    public function deleteFile(File $file): ?bool
    {
        try {

            // Catat siapa yang menghapus (dibaca oleh fitur Trash) sebelum soft delete,
            // karena SoftDeletes::delete() hanya menyimpan kolom deleted_at/updated_at.
            $file->update(['deleted_by' => Auth::id()]);

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

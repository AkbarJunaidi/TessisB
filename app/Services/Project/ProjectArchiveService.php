<?php

namespace App\Services\Project;

use App\Models\File;
use App\Models\Project;
use App\Models\Purchase;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\DataIntegration\FileService;
use App\Services\DataIntegration\FolderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Arsip PDF sebuah project (detail, keuangan, kwitansi, pembelian, surat jalan) yang disimpan
 * ke folder project di Integrasi Data; dipakai sebagai acuan isi ulang manual setelah project dihapus.
 */
class ProjectArchiveService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected FolderService $folderService,
        protected FileService $fileService
    ) {
    }

    /**
     * Boleh dipanggil untuk project aktif maupun yang sudah di Sampah.
     *
     * @throws RuntimeException bila arsip gagal dibuat.
     */
    public function createArchive(Project $project): File
    {
        $storedPath = null;

        try {
            $binary = Pdf::loadView('project.pdf.archive', $this->buildData($project))
                ->setPaper('a4', 'portrait')
                ->output();

            $now = now();
            $storedPath = File::ARCHIVE_DIR . '/' . (Str::slug($project->name) ?: 'project')
                . '-' . $now->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.pdf';

            if (!Storage::disk('public')->put($storedPath, $binary)) {
                throw new RuntimeException('Berkas arsip tidak dapat disimpan.');
            }

            $folder = $this->folderService->getOrCreateProjectFolder($project);

            $file = $this->fileService->registerGeneratedFile(
                folderId: $folder->id,
                storedPath: $storedPath,
                displayName: 'Arsip Project - ' . $project->name . ' - ' . $now->format('d-m-Y H.i') . '.pdf',
                fileSize: strlen($binary),
                fileType: 'pdf'
            );
        } catch (Throwable $e) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }

            report($e);

            throw new RuntimeException('Arsip project gagal dibuat. Coba lagi.', 0, $e);
        }

        $this->activityLogService->log(Auth::id(), 'Tracking Progress', "Membuat arsip project {$project->name}");

        return $file;
    }

    /**
     * Seluruh data yang ditampilkan di PDF arsip.
     *
     * @return array<string, mixed>
     */
    private function buildData(Project $project): array
    {
        $project->loadMissing('creator');

        $items = $project->financeItems()->with('contact')->orderBy('tanggal')->orderBy('id')->get();
        $kwitansis = $project->kwitansis()->orderBy('tanggal')->orderBy('id')->get();

        $income = (float) $items->where('type', 'income')->sum('amount');
        $expense = (float) $items->where('type', 'expense')->sum('amount');
        $kwitansiActive = (float) $kwitansis->where('status', 'Aktif')->sum('jumlah');
        $received = Project::breakdownFrom($items, $kwitansis)['received'];

        return [
            'project'      => $project,
            'incomes'      => $items->where('type', 'income')->values(),
            'expenses'     => $items->where('type', 'expense')->values(),
            'kwitansis'    => $kwitansis,
            'purchases'    => Purchase::where('project_id', $project->id)->orderBy('purchase_date')->orderBy('id')->get(),
            'suratJalans'  => $project->suratJalans()->orderBy('tanggal_terbit')->orderBy('id')->get(),
            'totalIncome'  => $income,
            'totalExpense' => $expense,
            'kwitansiActive' => $kwitansiActive,
            'received'     => $received,
            'remaining'    => max(0.0, (float) $project->estimated_value - $received),
            'createdBy'    => Auth::user()?->name,
            'createdAt'    => now(),
        ];
    }
}

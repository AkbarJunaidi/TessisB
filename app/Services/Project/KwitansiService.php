<?php

namespace App\Services\Project;

use App\Models\ApprovalRequest;
use App\Models\Kwitansi;
use App\Models\Project;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Approval\ApprovalService;
use App\Services\DataIntegration\FileService;
use App\Services\DataIntegration\FolderService;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class KwitansiService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected FolderService $folderService,
        protected FileService $fileService,
        protected ApprovalService $approvalService
    ) {
    }

    /**
     * @param  array{tanggal: string, jumlah: float, metode_pembayaran: ?string, keterangan: ?string, kategori_pembayaran: ?string, signature_id: ?int}  $data
     */
    public function create(Project $project, array $data): Kwitansi
    {
        return DB::transaction(function () use ($project, $data) {
            $kwitansi = Kwitansi::create([
                'nomor'               => $this->generateNomor(),
                'project_id'          => $project->id,
                'created_by'          => Auth::id(),
                'signature_id'        => $data['signature_id'] ?? null,
                'tanggal'             => $data['tanggal'],
                'jumlah'              => $data['jumlah'],
                'metode_pembayaran'   => $data['metode_pembayaran'] ?? null,
                'keterangan'          => $data['keterangan'] ?? null,
                'kategori_pembayaran' => $data['kategori_pembayaran'] ?? null,
                'status'              => 'Aktif',
            ]);

            $this->activityLogService->log(
                Auth::id(),
                'Data Keuangan',
                "Membuat Kwitansi {$kwitansi->nomor} untuk project \"{$project->name}\""
            );

            return $kwitansi;
        });
    }

    protected function generateNomor(): string
    {
        $year = now()->format('Y');

        $lastNumber = Kwitansi::where('nomor', 'like', "KWT-{$year}-%")
            ->orderByDesc('id')
            ->value('nomor');

        $nextSequence = $lastNumber ? ((int) substr($lastNumber, -4) + 1) : 1;

        return sprintf('KWT-%s-%04d', $year, $nextSequence);
    }

    /**
     * Ajukan pembatalan lewat Approval - TIDAK langsung membatalkan.
     * markVoided() di bawah hanya dipanggil ApprovalService setelah
     * permintaan ini disetujui Super Admin.
     */
    public function requestVoid(Kwitansi $kwitansi, string $reason): ApprovalRequest
    {
        if ($kwitansi->status !== 'Aktif') {
            throw new Exception('Kwitansi ini sudah tidak aktif.');
        }

        $kwitansi->loadMissing('project');

        $request = $this->approvalService->submit(
            'kwitansi_void',
            $kwitansi,
            $reason,
            [
                'nomor'        => $kwitansi->nomor,
                'project_name' => $kwitansi->project->name,
                'jumlah'       => (float) $kwitansi->jumlah,
            ]
        );

        $this->activityLogService->log(
            Auth::id(),
            'Data Keuangan',
            "Mengajukan pembatalan Kwitansi {$kwitansi->nomor}"
        );

        return $request;
    }

    /**
     * Dipanggil HANYA oleh ApprovalService::approve() - jangan dipanggil
     * langsung dari controller mana pun.
     */
    public function markVoided(Kwitansi $kwitansi): void
    {
        $kwitansi->update(['status' => 'Dibatalkan']);

        $this->activityLogService->log(
            Auth::id(),
            'Data Keuangan',
            "Kwitansi {$kwitansi->nomor} dibatalkan (disetujui lewat Approval)"
        );
    }

    public function generatePdf(Kwitansi $kwitansi, bool $stream = true)
    {
        $kwitansi->loadMissing('project', 'creator', 'signature');

        $pdf = Pdf::loadView('kwitansi.pdf', [
            'kwitansi'  => $kwitansi,
            'terbilang' => Terbilang::make($kwitansi->jumlah),
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = $kwitansi->nomor . '.pdf';
        $storedPath = 'kwitansi/' . $filename;
        $pdfBinary = $pdf->output();
        Storage::disk('public')->put($storedPath, $pdfBinary);

        $projectFolder = $this->folderService->getOrCreateProjectFolder($kwitansi->project);
        $this->fileService->registerGeneratedFile(
            folderId: $projectFolder->id,
            storedPath: $storedPath,
            displayName: 'Kwitansi - ' . $filename,
            fileSize: strlen($pdfBinary),
            fileType: 'pdf'
        );

        $this->activityLogService->log(
            Auth::id(),
            'Data Keuangan',
            "Preview/Download Kwitansi {$kwitansi->nomor}"
        );

        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * Ringkasan tagihan/pembayaran semua project untuk halaman "Keuangan" -
     * SEKARANG termasuk Pendapatan dari tab Data Keuangan (bukan cuma
     * Kwitansi), supaya kedua sumber uang yang sudah diterima "kelihatan"
     * di sini. Keduanya dihitung sebagai SQL SUM (withSum) supaya tidak
     * N+1. Alias SENGAJA dibuat beda dari nama accessor Project (lihat
     * Project::totalDibayar()/totalDiterima()) supaya tidak bentrok.
     */
    public function getProjectPaymentSummaries(?string $search = null): LengthAwarePaginator
    {
        return Project::query()
            ->select(['id', 'name', 'client', 'company', 'estimated_value', 'event_date'])
            ->withSum(['kwitansis as kwitansi_total_dibayar' => fn ($q) => $q->where('status', 'Aktif')], 'jumlah')
            ->withSum(['financeItems as pendapatan_tercatat' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%");
            }))
            ->orderByDesc('event_date')
            ->paginate(15)
            ->withQueryString();
    }
}

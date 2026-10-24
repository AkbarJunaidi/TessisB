<?php

namespace App\Services\Project;

use App\Support\PerPage;
use App\Models\ApprovalRequest;
use App\Models\Kwitansi;
use App\Models\Project;
use App\Models\ProjectFinanceItem;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Approval\ApprovalService;
use App\Services\DataIntegration\FileService;
use App\Services\DataIntegration\FolderService;
use App\Support\FinanceCategory;
use App\Support\FinanceLock;
use App\Support\Money;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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

    private const CATEGORY_LABELS = ['booking_fee' => 'Booking Fee', 'dp' => 'DP', 'pelunasan' => 'Pelunasan'];

    /**
     * Pemeriksaan sebelum simpan (tidak menulis apa pun): kelebihan bayar dan pemasukan manual yang
     * mungkin pembayaran yang sama. Dilewati bila kwitansi akan ditautkan ke pemasukan yang dipilih.
     *
     * @param  array<string, mixed>  $data
     * @return array{overpay: ?array{received: float, estimate: float, total: float, excess: float}, candidates: Collection}
     * @throws Exception
     */
    public function precheck(Project $project, array $data): array
    {
        FinanceLock::assertOpen($data['tanggal']);

        $jumlah = (float) $data['jumlah'];

        if ($this->linkableRow($project, (int) ($data['link_item_id'] ?? 0), $jumlah)) {
            return ['overpay' => null, 'candidates' => new Collection()];
        }

        $estimate = (float) $project->estimated_value;
        $overpay = null;

        if ($estimate > 0) {
            $received = (float) Project::query()->withPaymentTotals()->findOrFail($project->id)->paymentBreakdown()['received'];

            if ($received + $jumlah > $estimate) {
                $overpay = [
                    'received' => $received,
                    'estimate' => $estimate,
                    'total'    => $received + $jumlah,
                    'excess'   => $received + $jumlah - $estimate,
                ];
            }
        }

        return ['overpay' => $overpay, 'candidates' => $this->linkCandidates($project, $jumlah)];
    }

    /**
     * Membuat kwitansi dan satu baris pemasukan di buku kas (atau menautkan pemasukan manual yang dipilih).
     * Project dikunci supaya klik ganda dan permintaan bersamaan diproses berurutan.
     *
     * @param  array{tanggal: string, jumlah: float, metode_pembayaran: ?string, keterangan: ?string, kategori_pembayaran: ?string, signature_id: ?int, link_item_id: ?int}  $data
     * @throws Exception
     */
    public function create(Project $project, array $data): Kwitansi
    {
        return DB::transaction(function () use ($project, $data) {
            Project::whereKey($project->id)->lockForUpdate()->first();

            FinanceLock::assertOpen($data['tanggal']);

            // Kiriman identik dari user yang sama dalam 15 detik dianggap klik ganda, bukan kwitansi baru.
            $duplicate = Kwitansi::where('project_id', $project->id)
                ->where('created_by', Auth::id())
                ->where('status', 'Aktif')
                ->whereDate('tanggal', $data['tanggal'])
                ->where('jumlah', $data['jumlah'])
                ->where('created_at', '>=', now()->subSeconds(15))
                ->first();

            if ($duplicate) {
                return $duplicate;
            }

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

            $linked = $this->attachLedgerRow($project, $kwitansi, (int) ($data['link_item_id'] ?? 0));

            $this->activityLogService->log(
                Auth::id(),
                'Data Keuangan',
                "Membuat Kwitansi {$kwitansi->nomor} untuk project \"{$project->name}\" (" . Money::formatRupiah($kwitansi->jumlah) . ') - '
                . ($linked ? 'ditautkan ke pemasukan yang sudah ada' : 'dicatat otomatis di buku kas')
            );

            return $kwitansi;
        });
    }

    /** Pemasukan manual (belum tertaut, di periode terbuka) dengan nominal sama persis, kandidat pembayaran yang sama. */
    private function linkCandidates(Project $project, float $jumlah): Collection
    {
        $lock = FinanceLock::date();

        return $project->financeItems()
            ->whereNull('source_type')
            ->clientPayments()
            ->where('amount', $jumlah)
            ->when($lock, fn ($q) => $q->whereDate('tanggal', '>', $lock))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get(['id', 'tanggal', 'description', 'amount']);
    }

    /** Baris manual yang boleh ditautkan: milik project ini, belum tertaut, nominal sama, periode terbuka. */
    private function linkableRow(Project $project, int $itemId, float $jumlah): ?ProjectFinanceItem
    {
        if ($itemId <= 0) {
            return null;
        }

        $row = $project->financeItems()
            ->whereNull('source_type')
            ->clientPayments()
            ->whereKey($itemId)
            ->lockForUpdate()
            ->first();

        if (! $row || (float) $row->amount !== $jumlah || FinanceLock::isLocked($row->tanggal)) {
            return null;
        }

        return $row;
    }

    /** @return bool true bila menautkan baris manual, false bila membuat baris baru. */
    private function attachLedgerRow(Project $project, Kwitansi $kwitansi, int $linkItemId): bool
    {
        $row = $this->linkableRow($project, $linkItemId, (float) $kwitansi->jumlah);

        if ($row) {
            $row->update(['source_type' => ProjectFinanceItem::SOURCE_KWITANSI, 'source_id' => $kwitansi->id]);

            return true;
        }

        $label = self::CATEGORY_LABELS[$kwitansi->kategori_pembayaran] ?? null;

        ProjectFinanceItem::create([
            'project_id'     => $project->id,
            'type'           => 'income',
            'amount'         => $kwitansi->jumlah,
            'tanggal'        => $kwitansi->tanggal->toDateString(),
            'category'       => FinanceCategory::PROJECT_INCOME,
            'contact_id'     => $project->contact_id,
            'payment_method' => $kwitansi->metode_pembayaran,
            'source_type'    => ProjectFinanceItem::SOURCE_KWITANSI,
            'source_id'      => $kwitansi->id,
            'created_by'     => Auth::id(),
            'description'    => "Kwitansi {$kwitansi->nomor}" . ($label ? " ({$label})" : ''),
        ]);

        return false;
    }

    /**
     * Peringatan bila pemasukan manual yang baru disimpan senilai dengan Kwitansi yang sudah otomatis
     * tercatat di buku kas. Kwitansi lama (belum tertaut) tidak diperingatkan: rumus total sudah mencegah dobel.
     *
     * @param  array<int, mixed>  $amounts
     */
    public function manualIncomeWarning(?int $projectId, array $amounts): ?string
    {
        $amounts = array_values(array_unique(array_map('floatval', $amounts)));

        if (! $projectId || $amounts === []) {
            return null;
        }

        $kwitansi = Kwitansi::where('project_id', $projectId)
            ->where('status', 'Aktif')
            ->whereIn('jumlah', $amounts)
            ->whereHas('financeItem')
            ->orderBy('id')
            ->first();

        if (! $kwitansi) {
            return null;
        }

        return "Kwitansi {$kwitansi->nomor} senilai " . Money::formatRupiah($kwitansi->jumlah)
            . ' sudah otomatis tercatat di buku kas. Jika pemasukan yang baru disimpan adalah pembayaran yang sama, hapus agar tidak terhitung dua kali.';
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

        $this->assertVoidPeriodOpen($kwitansi);

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
     * Dipanggil HANYA oleh ApprovalService::approve() (sudah dalam transaksinya): periode dicek ulang, lalu
     * status, baris buku kas, dan log berubah bersama. Bila ditolak, Approval tetap menunggu keputusan.
     *
     * @throws Exception
     */
    public function markVoided(Kwitansi $kwitansi): void
    {
        DB::transaction(function () use ($kwitansi) {
            $fresh = Kwitansi::whereKey($kwitansi->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== 'Aktif') {
                throw new Exception('Kwitansi ini sudah tidak aktif.');
            }

            $this->assertVoidPeriodOpen($fresh);

            $fresh->update(['status' => 'Dibatalkan']);
            $fresh->financeItem?->delete();

            $this->activityLogService->log(
                Auth::id(),
                'Data Keuangan',
                "Kwitansi {$fresh->nomor} dibatalkan (" . Money::formatRupiah($fresh->jumlah) . ', disetujui lewat Approval)'
            );
        });

        $kwitansi->refresh();
    }

    /** @throws Exception */
    private function assertVoidPeriodOpen(Kwitansi $kwitansi): void
    {
        $dates = array_filter([$kwitansi->tanggal, $kwitansi->financeItem?->tanggal]);

        foreach ($dates as $date) {
            if (FinanceLock::isLocked($date)) {
                throw new Exception('Kwitansi ' . $kwitansi->nomor . ' bertanggal ' . $date->format('d/m/Y') . ' berada di periode keuangan yang sudah ditutup, jadi tidak bisa dibatalkan.');
            }
        }
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
     * Ringkasan tagihan semua project: total diterima dihitung di database
     * (Project::withPaymentTotals, tanpa N+1) dan dibaca lewat paymentBreakdown().
     */
    public function getProjectPaymentSummaries(?string $search = null): LengthAwarePaginator
    {
        return Project::query()
            ->select(['id', 'name', 'client', 'company', 'estimated_value', 'event_date'])
            ->withPaymentTotals()
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%");
            }))
            ->orderByDesc('event_date')
            ->paginate(PerPage::resolve(15))
            ->withQueryString();
    }
}

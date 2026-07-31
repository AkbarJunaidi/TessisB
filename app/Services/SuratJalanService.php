<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Project;
use App\Models\SuratJalan;
use App\Models\SuratJalanItem;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\DataIntegration\FileService;
use App\Services\DataIntegration\FolderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class SuratJalanService
{
    protected ActivityLogService $activityLogService;
    protected FolderService $folderService;
    protected FileService $fileService;

    public function __construct(
        ActivityLogService $activityLogService,
        FolderService $folderService,
        FileService $fileService
    ) {
        $this->activityLogService = $activityLogService;
        $this->folderService = $folderService;
        $this->fileService = $fileService;
    }

    /**
     * Membuat Surat Jalan baru beserta item barangnya.
     * Stok langsung berkurang saat submit (bukan saat print), sesuai kesepakatan.
     * Menggunakan row lock (lockForUpdate) pada Inventory agar aman dari race condition
     * apabila dua Surat Jalan dibuat bersamaan untuk barang yang sama.
     *
     * @throws Exception
     */
    public function createSuratJalan(Project $project, array $data): SuratJalan
    {
        return DB::transaction(function () use ($project, $data) {

            $suratJalan = SuratJalan::create([
                'nomor'                  => $this->generateNomor(),
                'project_id'             => $project->id,
                'created_by'             => Auth::id(),
                'kepada'                 => $data['kepada'],
                'keperluan'              => $data['keperluan'],
                'pic'                    => $data['pic'],
                'tanggal_terbit'         => $data['tanggal_terbit'],
                'tanggal_keberangkatan'  => $data['tanggal_keberangkatan'] ?? null,
                'jam_berangkat'          => $data['jam_berangkat'] ?? null,
                'tanggal_gladi_bersih'   => $data['tanggal_gladi_bersih'] ?? null,
                'waktu_gladi_bersih'     => $data['waktu_gladi_bersih'] ?? null,
                'tanggal_acara'          => $data['tanggal_acara'] ?? null,
                'tanggal_acara_selesai'  => $data['tanggal_acara_selesai'] ?? null,
                'waktu_acara'            => $data['waktu_acara'] ?? null,
                'lokasi_acara'           => $data['lokasi_acara'],
                'catatan'                => $data['catatan'] ?? null,
                'status'                 => 'Aktif',
            ]);

            foreach ($data['items'] as $row) {
                // Row lock: kunci baris inventory selama transaksi agar qty_available
                // yang dibaca benar-benar akurat, aman dari request bersamaan (double booking).
                $inventory = Inventory::where('id', $row['inventory_id'])->lockForUpdate()->first();

                if (!$inventory) {
                    throw new Exception('Barang tidak ditemukan.');
                }

                if ($row['qty'] > $inventory->qty_available) {
                    throw new Exception("Stok \"{$inventory->name}\" tidak mencukupi saat penyimpanan (kemungkinan diambil Surat Jalan lain secara bersamaan).");
                }

                $suratJalanItem = SuratJalanItem::create([
                    'surat_jalan_id'   => $suratJalan->id,
                    'inventory_id'     => $inventory->id,
                    'qty_dipakai'      => $row['qty'],
                    'qty_dikembalikan' => 0,
                ]);
                // Catatan: qty_available Inventory dihitung otomatis (accessor) dari
                // SUM(qty_dipakai - qty_dikembalikan) pada surat_jalan_items, sehingga
                // baris di atas SUDAH otomatis "mengurangi" stok tanpa kolom terpisah.

                // Tandai unit fisik spesifik mana yang benar-benar keluar (bukan cuma
                // hitungan qty), supaya "Kelola Unit Fisik" & "Unit Tersedia" akurat
                // menampilkan unit mana yang sedang dipakai.
                $unitsToAssign = $inventory->units()
                    ->where('status', 'Tersedia')
                    ->whereNull('surat_jalan_item_id')
                    ->orderBy('unit_number')
                    ->limit($row['qty'])
                    ->get();

                if ($unitsToAssign->count() < $row['qty']) {
                    throw new Exception("Unit fisik \"{$inventory->name}\" yang benar-benar tersedia tidak mencukupi.");
                }

                \App\Models\InventoryUnit::whereIn('id', $unitsToAssign->pluck('id'))
                    ->update(['surat_jalan_item_id' => $suratJalanItem->id]);
            }

            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                "Membuat Surat Jalan {$suratJalan->nomor} untuk project \"{$project->name}\""
            );

            return $suratJalan->load('items.inventory', 'project.crews');
        });
    }

    /**
     * Nomor Surat Jalan dibuat otomatis oleh sistem (format SJ-{tahun}-{urutan 4 digit})
     * agar terjamin unik dan tidak bergantung pada input manual yang rawan salah/duplikat.
     */
    protected function generateNomor(): string
    {
        $year = now()->format('Y');

        $lastNumber = SuratJalan::withTrashed()
            ->where('nomor', 'like', "SJ-{$year}-%")
            ->orderByDesc('id')
            ->value('nomor');

        $nextSequence = 1;
        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, -4);
            $nextSequence = $lastSequence + 1;
        }

        return sprintf('SJ-%s-%04d', $year, $nextSequence);
    }

    /**
     * Generate PDF Surat Jalan dengan layout formal ala template CV. Arindra Production,
     * sekaligus menyimpan salinannya ke storage (untuk diintegrasikan ke Document Center project).
     */
    public function generatePdf(SuratJalan $suratJalan, bool $stream = true)
    {
        $suratJalan->loadMissing('items.inventory', 'project.crews', 'creator');

        $pdf = Pdf::loadView('surat-jalan.pdf', ['suratJalan' => $suratJalan]);
        $pdf->setPaper('a4', 'portrait');

        $filename = $suratJalan->nomor . '.pdf';

        // Simpan salinan fisik ke storage
        $storedPath = 'surat-jalan/' . $filename;
        $pdfBinary = $pdf->output();
        Storage::disk('public')->put($storedPath, $pdfBinary);

        if ($suratJalan->file_path !== $storedPath) {
            $suratJalan->update(['file_path' => $storedPath]);
        }

        // PRD Bagian 9: PDF otomatis masuk folder Document Center milik project ini.
        $projectFolder = $this->folderService->getOrCreateProjectFolder($suratJalan->project);
        $this->fileService->registerGeneratedFile(
            folderId: $projectFolder->id,
            storedPath: $storedPath,
            displayName: 'Surat Jalan - ' . $filename,
            fileSize: strlen($pdfBinary),
            fileType: 'pdf'
        );

        $this->activityLogService->log(
            Auth::id(),
            'Tracking Progress',
            "Preview/Download Surat Jalan {$suratJalan->nomor}"
        );

        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * Kembalikan barang (partial return). Menambah qty_dikembalikan pada satu baris item.
     * Jika seluruh item pada Surat Jalan sudah dikembalikan penuh, status berubah menjadi "Selesai".
     *
     * @throws Exception
     */
    public function returnItem(SuratJalanItem $item, int $qty): SuratJalanItem
    {
        return DB::transaction(function () use ($item, $qty) {
            $item = SuratJalanItem::where('id', $item->id)->lockForUpdate()->first();

            $sisa = $item->qty_dipakai - $item->qty_dikembalikan;
            if ($qty > $sisa) {
                throw new Exception("Jumlah pengembalian melebihi sisa barang yang masih dipakai ({$sisa} unit).");
            }

            $item->update([
                'qty_dikembalikan' => $item->qty_dikembalikan + $qty,
            ]);

            // Lepaskan sejumlah `$qty` unit fisik yang tadinya terhubung ke item ini,
            // supaya unit itu kembali terlihat "Tersedia" (bukan "Dipakai") lagi.
            $unitsToRelease = \App\Models\InventoryUnit::where('surat_jalan_item_id', $item->id)
                ->orderBy('unit_number')
                ->limit($qty)
                ->get();

            \App\Models\InventoryUnit::whereIn('id', $unitsToRelease->pluck('id'))
                ->update(['surat_jalan_item_id' => null]);

            $suratJalan = $item->suratJalan()->with('items')->first();
            $allReturned = $suratJalan->items->every(fn ($i) => $i->qty_dikembalikan >= $i->qty_dipakai);

            if ($allReturned && $suratJalan->status !== 'Selesai') {
                $suratJalan->update(['status' => 'Selesai']);
            }

            $this->activityLogService->log(
                Auth::id(),
                'Tracking Progress',
                "Mengembalikan {$qty} unit barang pada Surat Jalan {$suratJalan->nomor}"
            );

            return $item->fresh();
        });
    }
}

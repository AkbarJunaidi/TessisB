<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryAttribute;
use App\Models\ReportExport;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class InventoryService
{
    protected ActivityLogService $activityLogService;
    protected QrCodeService $qrCodeService;

    // Dependency Injection untuk ActivityLogService dan QrCodeService
    public function __construct(
        ActivityLogService $activityLogService,
        QrCodeService $qrCodeService
    ) {
        $this->activityLogService = $activityLogService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Mengambil seluruh data inventory dengan pagination, filter nama, dan status.
     * Search HANYA berdasarkan Nama Barang dan Filter Dropdown Status.
     */
    public function getAllPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Inventory::withAvailability();

        // Search berdasarkan Nama Barang ATAU Brand
        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('brand', 'like', '%' . $keyword . '%');
            });
        }

        // Filter berdasarkan Dropdown Status
        if (!empty($filters['status']) && $filters['status'] !== 'Semua Status') {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Menambahkan data inventory baru beserta description, status, QR Code, dan atribut dinamis.
     */
    public function createInventory(array $data, ?UploadedFile $imageFile = null): Inventory
    {
        return DB::transaction(function () use ($data, $imageFile) {
            $imagePath = null;

            if ($imageFile) {
                $imagePath = $imageFile->store('assets/inventory/images', 'public');
            }

            // Buat record awal di database
            $inventory = Inventory::create([
                'name'          => $data['name'],
                'serial_number' => $data['serial_number'],
                'description'   => $data['description'] ?? null,
                'status'        => $data['status'] ?? 'Tersedia',
                'brand'         => $data['brand'] ?? null,
                'image'         => $imagePath,
                'qr_code'       => null,
                'quantity_total' => $data['quantity_total'] ?? 1,
            ]);

            // Generate file fisik QR Code berdasarkan serial_number
            $qrCodePath = $this->qrCodeService->generate($inventory->serial_number);

            $inventory->update([
                'qr_code' => $qrCodePath,
            ]);

            // Generate Unit Fisik sejumlah quantity_total (1 serial number/QR untuk seluruh unit)
            $this->syncUnits($inventory, $inventory->quantity_total);

            // Simpan Informasi Tambahan (Dynamic Attributes) jika opsi dipilih
            $useAttributes = filter_var($data['use_attributes'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($useAttributes && !empty($data['attributes']) && is_array($data['attributes'])) {
                $this->saveAttributes($inventory, $data['attributes']);
            }

            // Logging aktivitas
            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                'Create Inventory'
            );

            return $inventory;
        });
    }

    /**
     * Mengubah data inventory, foto, QR Code, status, deskripsi, serta sinkronisasi atribut dinamis.
     */
    public function updateInventory(
        Inventory $inventory,
        array $data,
        ?UploadedFile $imageFile = null
    ): Inventory {
        return DB::transaction(function () use ($inventory, $data, $imageFile) {

            if ($imageFile) {
                if ($inventory->image && Storage::disk('public')->exists($inventory->image)) {
                    Storage::disk('public')->delete($inventory->image);
                }

                $inventory->image = $imageFile->store(
                    'assets/inventory/images',
                    'public'
                );
            } elseif (!empty($data['remove_image'])) {
                if ($inventory->image && Storage::disk('public')->exists($inventory->image)) {
                    Storage::disk('public')->delete($inventory->image);
                }
                $inventory->image = null;
            }

            // Catat nomor seri lama untuk mendeteksi perubahan lifecycle file
            $oldSerialNumber = $inventory->serial_number;
            $oldQrPath = $inventory->qr_code;

            // Jalankan update database data tekstual
            $inventory->update([
                'name'          => $data['name'],
                'serial_number' => $data['serial_number'],
                'description'   => $data['description'] ?? null,
                'status'        => $data['status'] ?? $inventory->status,
                'brand'         => $data['brand'] ?? null,
                'image'         => $inventory->image,
                'quantity_total' => $data['quantity_total'] ?? $inventory->quantity_total,
            ]);

            // Logika Siklus Hidup File QR Code
            if ($oldSerialNumber !== $inventory->serial_number) {
                // Jika serial number diubah, bersihkan file gambar lama dari disk
                if ($oldQrPath) {
                    $this->qrCodeService->delete($oldQrPath);
                }

                // Generate file gambar QR baru dengan identitas serial number yang baru
                $newQrPath = $this->qrCodeService->generate($inventory->serial_number);

                // Simpan path baru ke kolom tabel database
                $inventory->update([
                    'qr_code' => $newQrPath,
                ]);
            } else {
                // Jika serial number tidak berubah, pastikan file fisik tetap ada
                $currentQrPath = $this->qrCodeService->generate($inventory->serial_number);

                if ($inventory->qr_code !== $currentQrPath) {
                    $inventory->update([
                        'qr_code' => $currentQrPath,
                    ]);
                }
            }

            // Sinkronisasi Unit Fisik mengikuti perubahan quantity_total
            $this->syncUnits($inventory, $inventory->quantity_total);

            // Sinkronisasi Informasi Tambahan (Dynamic Attributes)
            $useAttributes = filter_var($data['use_attributes'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($useAttributes && !empty($data['attributes']) && is_array($data['attributes'])) {
                $this->syncAttributes($inventory, $data['attributes']);
            } else {
                // Jika user memilih "Tidak", hapus seluruh atribut dinamis aset ini
                $inventory->attributes()->delete();
            }

            // Logging aktivitas
            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                'Update Inventory'
            );

            return $inventory->fresh(['attributes']);
        });
    }

    /**
     * Menyinkronkan Unit Fisik agar jumlahnya selalu sama dengan quantity_total.
     * - Jika quantity_total bertambah, unit baru dibuat dengan status default "Tersedia".
     * - Jika quantity_total berkurang, unit dengan nomor tertinggi akan dihapus,
     *   TAPI hanya yang berstatus "Tersedia" (aman dihapus). Jika unit yang harus
     *   dihapus ternyata berstatus Rusak/Perbaikan/Hilang, proses dibatalkan dengan
     *   pesan error agar admin menyelesaikan status unit tersebut dulu.
     *
     * @throws \Exception
     */
    private function syncUnits(Inventory $inventory, int $targetQuantity): void
    {
        $currentUnits = $inventory->units()->orderBy('unit_number')->get();
        $currentCount = $currentUnits->count();

        if ($targetQuantity > $currentCount) {
            $newUnits = [];
            for ($i = $currentCount + 1; $i <= $targetQuantity; $i++) {
                $newUnits[] = [
                    'inventory_id' => $inventory->id,
                    'unit_number'  => $i,
                    'status'       => 'Tersedia',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
            \App\Models\InventoryUnit::insert($newUnits);
        } elseif ($targetQuantity < $currentCount) {
            $unitsToRemove = $currentUnits->sortByDesc('unit_number')->take($currentCount - $targetQuantity);

            $nonTersedia = $unitsToRemove->firstWhere('status', '!=', 'Tersedia');
            if ($nonTersedia) {
                throw new \Exception(
                    "Tidak bisa mengurangi Jumlah Barang: Unit #{$nonTersedia->unit_number} berstatus \"{$nonTersedia->status}\" (bukan Tersedia). Selesaikan status unit tersebut terlebih dahulu."
                );
            }

            $sedangDipinjam = $unitsToRemove->firstWhere('surat_jalan_item_id', '!=', null);
            if ($sedangDipinjam) {
                throw new \Exception(
                    "Tidak bisa mengurangi Jumlah Barang: Unit #{$sedangDipinjam->unit_number} sedang dipinjam lewat Surat Jalan. Tunggu sampai unit itu dikembalikan."
                );
            }

            \App\Models\InventoryUnit::whereIn('id', $unitsToRemove->pluck('id'))->delete();
        }
    }

    /**
     * Mengubah status kondisi 1 unit fisik (dipakai endpoint AJAX per-baris).
     */
    public function updateUnitStatus(\App\Models\InventoryUnit $unit, string $status): \App\Models\InventoryUnit
    {
        if ($unit->surat_jalan_item_id) {
            throw new \Exception("Unit #{$unit->unit_number} sedang dipinjam - status tidak bisa diubah manual sampai dikembalikan.");
        }

        $unit->update(['status' => $status]);

        $this->activityLogService->log(
            Auth::id(),
            'Inventory',
            "Ubah status Unit #{$unit->unit_number} ({$unit->inventory->name}) menjadi \"{$status}\""
        );

        return $unit->fresh();
    }

    /**
     * Soft delete inventory agar bisa di-restore.
     */
    public function deleteInventory(Inventory $inventory): bool
    {
        // Catat siapa yang menghapus (dibaca oleh fitur Trash) sebelum soft delete,
        // karena SoftDeletes::delete() hanya menyimpan kolom deleted_at/updated_at.
        $inventory->update(['deleted_by' => Auth::id()]);

        $deleted = $inventory->delete();

        if ($deleted) {
            $this->activityLogService->log(
                Auth::id(),
                'Inventory',
                'Delete Inventory'
            );
        }
        return $deleted;
    }

    /**
     * Render stiker label QR.
     */
    public function generateLabelPdf(Inventory $inventory, bool $stream = false)
    {
        // Render data ke view HTML terisolasi khusus DomPDF yang menggunakan struktur tabel native
        $pdf = Pdf::loadView('inventory.pdf-qr', compact('inventory'));

        // Rumus konversi standar industri grafis: 1 mm = 2.83465 pt
        // 100mm = 283.46 pt (lebar), 50mm = 141.73 pt (tinggi)
        $pdf->setPaper([0, 0, 283.46, 141.73], 'portrait');

        // HASIL AKHIR NAMA FILE: LABEL-NAMABARANG
        $safeName = \Illuminate\Support\Str::slug($inventory->name);
        $fileName = 'label-' . $safeName . '.pdf';

        if ($stream) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    // MODUL EXTENSION BARU: INVENTORY REPORT PDF CORE BUSINESS LOGIC

    public function generateSingleReport(Inventory $inventory, bool $stream = true)
    {
        // Pastikan relasi attributes sudah dimuat (loadMissing menghindari query ulang jika sudah ter-load sebelumnya)
        $inventory->loadMissing('attributes');

        // Menyusun tanggal pembuatan laporan formal sesuai standarisasi sistem informasi
        $exportDate = now()->translatedFormat('d F Y H:i');
        // Memuat template layout profesional terisolasi
        $pdf = Pdf::loadView('inventory.pdf.single', compact('inventory', 'exportDate'));
        // Memaksa driver printer untuk mengunci ukuran kertas ke standar internasional A4 Tegak
        $pdf->setPaper('a4', 'portrait');
        // Output penamaan berkas terstandardisasi: inventory-report-{serial_number}.pdf
        $filename = 'inventory-report-' . $inventory->serial_number . '.pdf';
        // Log audit sistem
        $this->activityLogService->log(Auth::id(), 'Inventory', 'Preview/Download Single PDF Report');
        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * Membuat objek PDF Laporan Massal Seluruh Inventaris - dipakai bersama
     * oleh preview/download langsung (generateAllReport) yang membaca semua
     * data sekaligus dalam 1 request. Untuk laporan berukuran besar, lihat
     * startAllReportExport()/processAllReportBatch() yang membangunnya
     * bertahap lewat beberapa request kecil.
     */
    public function buildAllInventoryReportPdf()
    {
        // Eager load attributes agar tidak terjadi N+1 saat Blade mengakses $inventory->attributes per item
        $inventories = Inventory::with('attributes')->latest()->get();
        $exportDate = now()->translatedFormat('d F Y H:i');

        $pdf = Pdf::loadView('inventory.pdf.all', compact('inventories', 'exportDate'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateAllReport(bool $stream = true)
    {
        $pdf = $this->buildAllInventoryReportPdf();

        // Output penamaan berkas massal: all-inventory-report-{timestamp}.pdf
        $filename = 'all-inventory-report-' . now()->format('YmdHis') . '.pdf';
        // Log audit sistem
        $this->activityLogService->log(Auth::id(), 'Inventory', 'Preview/Download All PDF Report');
        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * Memulai proses pembuatan Laporan Massal Seluruh Inventaris.
     *
     * Alih-alih diproses langsung dalam 1 request (berisiko timeout kalau
     * data banyak) ATAU lewat queue worker terpisah (butuh proses background
     * `php artisan queue:work` yang belum tentu tersedia di semua jenis
     * hosting) - laporan ini dibangun BERTAHAP lewat beberapa request kecil
     * (lihat processAllReportBatch()) yang dipicu berulang oleh JavaScript
     * di browser. Pendekatan ini tidak butuh setup tambahan apa pun di
     * server manapun (shared hosting, VPS, PaaS) karena hanya mengandalkan
     * request HTTP biasa yang berukuran kecil dan cepat selesai.
     */
    public function startAllReportExport(int $requestedBy): ReportExport
    {
        $reportExport = ReportExport::create([
            'type'            => 'inventory_all',
            'status'          => 'processing',
            'requested_by'    => $requestedBy,
            'total_items'     => Inventory::count(),
            'processed_items' => 0,
        ]);

        $this->activityLogService->log($requestedBy, 'Inventory', 'Mulai Proses Laporan Massal PDF');

        return $reportExport;
    }

    /**
     * Memproses 1 batch (potongan kecil) dari Laporan Massal - dipanggil
     * berulang kali oleh JavaScript sampai seluruh data selesai diproses.
     *
     * Setiap panggilan hanya mengerjakan sebagian kecil data ($batchSize
     * item), jadi durasinya selalu singkat dan aman dari timeout berapa pun
     * total jumlah datanya. HTML per item digabung bertahap ke file
     * sementara; begitu seluruh item selesai, baru dirender jadi 1 file PDF
     * utuh dan disimpan ke storage publik.
     *
     * @return array{finished: bool, status: string, processed: int, total: int, download_url: ?string, error: ?string}
     */
    public function processAllReportBatch(ReportExport $reportExport, int $batchSize = 25): array
    {
        if ($reportExport->status !== 'processing') {
            return $this->buildBatchProgress($reportExport);
        }

        try {

            $items = Inventory::with('attributes')
                ->orderBy('id')
                ->skip($reportExport->processed_items)
                ->take($batchSize)
                ->get();

            if ($items->isNotEmpty()) {

                // Tanggal generate dikunci ke waktu laporan DIMULAI (bukan
                // waktu tiap batch diproses), supaya seluruh halaman di PDF
                // akhir menampilkan tanggal generate yang sama persis.
                $exportDate = $reportExport->created_at->translatedFormat('d F Y H:i');

                $fragment = $items
                    ->map(fn (Inventory $inventory) => view('inventory.pdf.partials.all-item', [
                        'inventory'  => $inventory,
                        'exportDate' => $exportDate,
                    ])->render())
                    ->implode('');

                $this->appendToTempReport($reportExport, $fragment);

                $reportExport->increment('processed_items', $items->count());
            }

            if ($reportExport->fresh()->processed_items >= $reportExport->total_items) {
                $this->finalizeAllReportExport($reportExport);
            }

        } catch (Throwable $e) {

            $reportExport->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

        }

        return $this->buildBatchProgress($reportExport->fresh());
    }

    /**
     * Menyatukan seluruh fragmen HTML yang sudah terkumpul jadi 1 file PDF
     * utuh, menyimpannya ke storage publik, lalu membersihkan file sementara.
     */
    private function finalizeAllReportExport(ReportExport $reportExport): void
    {
        $bodyHtml = Storage::disk('local')->exists($this->tempReportPath($reportExport))
            ? Storage::disk('local')->get($this->tempReportPath($reportExport))
            : '';

        $pdf = Pdf::loadView('inventory.pdf.all-shell', compact('bodyHtml'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'all-inventory-report-' . now()->format('YmdHis') . '.pdf';
        $storedPath = 'inventory-reports/' . $filename;

        Storage::disk('public')->put($storedPath, $pdf->output());
        Storage::disk('local')->delete($this->tempReportPath($reportExport));

        $reportExport->update([
            'status'    => 'completed',
            'file_path' => $storedPath,
        ]);

        // Invalidate cache notifikasi navbar (lihat NotificationService),
        // supaya notifikasi "Laporan Siap Diunduh" langsung muncul.
        Cache::forget('notifications.active');

        $this->activityLogService->log($reportExport->requested_by, 'Inventory', 'Laporan Massal PDF Selesai');
    }

    /**
     * Membatalkan proses Laporan Massal yang sedang berjalan (atau yang baru
     * saja selesai/gagal) - menghapus TOTAL jejaknya: file sementara di
     * disk lokal, file PDF final di storage publik (kalau kebetulan sempat
     * selesai tepat sebelum dibatalkan), dan baris datanya sendiri di
     * database. Tidak ada yang tersisa sama sekali setelah ini.
     */
    public function cancelReportExport(ReportExport $reportExport): void
    {
        $tempPath = $this->tempReportPath($reportExport);
        if (Storage::disk('local')->exists($tempPath)) {
            Storage::disk('local')->delete($tempPath);
        }

        if ($reportExport->file_path && Storage::disk('public')->exists($reportExport->file_path)) {
            Storage::disk('public')->delete($reportExport->file_path);
        }

        $requestedBy = $reportExport->requested_by;

        $reportExport->delete();

        // Invalidate cache notifikasi navbar - jaga-jaga kalau laporan ini
        // sempat selesai tepat sebelum dibatalkan, supaya notifikasi
        // "Laporan Siap Diunduh" untuk laporan yang sudah dihapus ini tidak
        // ikut nyangkut di cache lama.
        Cache::forget('notifications.active');

        $this->activityLogService->log($requestedBy, 'Inventory', 'Batalkan Laporan Massal PDF');
    }

    /**
     * Menambahkan fragmen HTML baru ke file penampung sementara milik 1
     * laporan. Storage lokal tidak menyediakan operasi "append" bawaan,
     * jadi dilakukan baca-lalu-tulis-ulang - cukup ringan karena isinya
     * teks HTML per item (bukan gambar/PDF biner), bukan seluruh data
     * inventory.
     */
    private function appendToTempReport(ReportExport $reportExport, string $fragment): void
    {
        $path = $this->tempReportPath($reportExport);
        $existing = Storage::disk('local')->exists($path)
            ? Storage::disk('local')->get($path)
            : '';

        Storage::disk('local')->put($path, $existing . $fragment);
    }

    private function tempReportPath(ReportExport $reportExport): string
    {
        return 'tmp/report-exports/' . $reportExport->id . '.html';
    }

    /**
     * Bentuk response progres yang dikirim balik ke JavaScript setiap batch.
     */
    private function buildBatchProgress(ReportExport $reportExport): array
    {
        return [
            'finished'     => $reportExport->status !== 'processing',
            'status'       => $reportExport->status,
            'processed'    => $reportExport->processed_items,
            'total'        => $reportExport->total_items,
            'download_url' => $reportExport->status === 'completed'
                ? route('inventory.download-queued-report', $reportExport)
                : null,
            'error'        => $reportExport->status === 'failed'
                ? $reportExport->error_message
                : null,
        ];
    }

    /**
     * Helper privat untuk menyimpan atribut dinamis baru (mengabaikan baris kosong).
     */
    private function saveAttributes(Inventory $inventory, array $attributes): void
    {
        $attributesToInsert = [];

        foreach ($attributes as $attr) {
            $name = trim($attr['name'] ?? '');
            $value = trim($attr['value'] ?? '');

            // Anggap tidak ada jika seluruh baris kosong
            if ($name !== '' && $value !== '') {
                $attributesToInsert[] = [
                    'inventory_id'    => $inventory->id,
                    'attribute_name'  => $name,
                    'attribute_value' => $value,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        if (!empty($attributesToInsert)) {
            InventoryAttribute::insert($attributesToInsert);
        }
    }

    /**
     * Helper privat untuk mengganti total (sync) atribut dinamis.
     */
    private function syncAttributes(Inventory $inventory, array $attributes): void
    {
        $inventory->attributes()->delete();
        $this->saveAttributes($inventory, $attributes);
    }
}

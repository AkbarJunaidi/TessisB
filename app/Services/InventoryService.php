<?php

namespace App\Services;

use App\Models\Inventory;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\QrCodeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryService
{
    /**
     * Service untuk Activity Log.
     */
    protected ActivityLogService $activityLogService;

    /**
     * Service untuk Manajemen QR Code.
     */
    protected QrCodeService $qrCodeService;

    /**
     * Constructor.
     * Melakukan Dependency Injection untuk ActivityLogService dan QrCodeService.
     */
    public function __construct(
        ActivityLogService $activityLogService,
        QrCodeService $qrCodeService
    ) {
        $this->activityLogService = $activityLogService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Mengambil seluruh data inventory dengan pagination.
     */
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Inventory::latest()->paginate($perPage);
    }

    /**
     * Menambahkan data inventory baru beserta QR Code otomatis.
     */
    public function createInventory(array $data, ?UploadedFile $imageFile = null): Inventory
    {
        $imagePath = null;

        if ($imageFile) {
            $imagePath = $imageFile->store('assets/inventory/images', 'public');
        }

        // 1. Buat record awal di database
        $inventory = Inventory::create([
            'name'          => $data['name'],
            'serial_number' => $data['serial_number'],
            'image'         => $imagePath,
            'qr_code'       => null,
        ]);

        // 2. Generate file fisik QR Code berdasarkan serial_number
        $qrCodePath = $this->qrCodeService->generate($inventory->serial_number);

        // 3. Update kolom qr_code dengan path gambar yang valid
        $inventory->update([
            'qr_code' => $qrCodePath,
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'Inventory',
            'Create Inventory'
        );

        return $inventory;
    }

    /**
     * Mengubah data inventory dan memperbarui berkas QR Code jika serial number berubah.
     */
    public function updateInventory(
        Inventory $inventory,
        array $data,
        ?UploadedFile $imageFile = null
    ): Inventory {

        if ($imageFile) {
            if ($inventory->image) {
                Storage::disk('public')->delete($inventory->image);
            }

            $inventory->image = $imageFile->store(
                'assets/inventory/images',
                'public'
            );
        }

        // Catat nomor seri lama untuk mendeteksi perubahan lifecycle file
        $oldSerialNumber = $inventory->serial_number;
        $oldQrPath = $inventory->qr_code;

        // Jalankan update database data tekstual
        $inventory->update([
            'name'          => $data['name'],
            'serial_number' => $data['serial_number'],
            'image'         => $inventory->image,
        ]);

        // Logika Siklus Hidup File QR Code:
        if ($oldSerialNumber !== $inventory->serial_number) {
            // Jika serial number diubah, bersihkan file gambar lama dari disk
            $this->qrCodeService->delete($oldQrPath);

            // Generate file gambar QR baru dengan identitas serial number yang baru
            $newQrPath = $this->qrCodeService->generate($inventory->serial_number);

            // Simpan path baru ke kolom tabel database
            $inventory->update([
                'qr_code' => $newQrPath,
            ]);
        } else {
            // Jika serial number tidak berubah, pastikan file fisik tetap ada (antisipasi file terhapus manual)
            $currentQrPath = $this->qrCodeService->generate($inventory->serial_number);

            if ($inventory->qr_code !== $currentQrPath) {
                $inventory->update([
                    'qr_code' => $currentQrPath,
                ]);
            }
        }

        $this->activityLogService->log(
            Auth::id(),
            'Inventory',
            'Update Inventory'
        );

        return $inventory;
    }

    /**
     * Menghapus inventory (Soft Delete).
     * Berkas fisik sengaja dipertahankan agar identitas item tetap valid saat data di-restore.
     */
    public function deleteInventory(Inventory $inventory): bool
    {
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
     * Merender stiker barcode label bawaan (100mm x 50mm).
     */
    public function generateLabelPdf(Inventory $inventory, bool $stream = false)
    {
        // 1. Render data ke view HTML terisolasi khusus DomPDF yang menggunakan struktur tabel native
        $pdf = Pdf::loadView('inventory.pdf-qr', compact('inventory'));

        // Rumus konversi standar industri grafis: 1 mm = 2.83465 pt
        // 100mm = 283.46 pt (lebar), 50mm = 141.73 pt (tinggi)
        $pdf->setPaper([0, 0, 283.46, 141.73], 'portrait');

        // HASIL AKHIR NAMA FILE: LABEL-NAMABARANG
        $safeName = \Illuminate\Support\Str::slug($inventory->name);
        $fileName = 'label-' . $safeName . '.pdf';

        // 4. Routing output aksi sesuai permintaan instruksi parameter stream
        if ($stream) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    // MODUL EXTENSION BARU: INVENTORY REPORT PDF CORE BUSINESS LOGIC

    public function generateSingleReport(Inventory $inventory, bool $stream = true)
    {
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

    public function generateAllReport(bool $stream = true)
    {
        // Menarik semua data tanpa batasan pagination (Latest First)
        $inventories = Inventory::latest()->get();
        $exportDate = now()->translatedFormat('d F Y H:i');

        // Memuat view bundle massal
        $pdf = Pdf::loadView('inventory.pdf.all', compact('inventories', 'exportDate'));

        // Memaksa driver printer untuk mengunci ukuran kertas ke standar internasional A4 Tegak
        $pdf->setPaper('a4', 'portrait');

        // Output penamaan berkas massal: all-inventory-report-{timestamp}.pdf
        $filename = 'all-inventory-report-' . now()->format('YmdHis') . '.pdf';

        // Log audit sistem
        $this->activityLogService->log(Auth::id(), 'Inventory', 'Preview/Download All PDF Report');

        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
    }
}

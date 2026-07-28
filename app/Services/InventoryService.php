<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryAttribute;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\QrCodeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Search HANYA berdasarkan Nama Barang
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . trim($filters['search']) . '%');
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
     * Soft delete inventory agar bisa di-restore.
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

    public function generateAllReport(bool $stream = true)
    {
        // Eager load attributes agar tidak terjadi N+1 saat Blade mengakses $inventory->attributes per item
        $inventories = Inventory::with('attributes')->latest()->get();
        $exportDate = now()->translatedFormat('d F Y H:i');
        // Memuat view bundle massal
        $pdf = Pdf::loadView('inventory.pdf.all', compact('inventories', 'exportDate'));

        $pdf->setPaper('a4', 'portrait');
        // Output penamaan berkas massal: all-inventory-report-{timestamp}.pdf
        $filename = 'all-inventory-report-' . now()->format('YmdHis') . '.pdf';
        // Log audit sistem
        $this->activityLogService->log(Auth::id(), 'Inventory', 'Preview/Download All PDF Report');
        return $stream ? $pdf->stream($filename) : $pdf->download($filename);
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

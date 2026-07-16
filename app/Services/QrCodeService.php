<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    public function generate(string $serialNumber): string
    {
        $directory = 'qrcodes';
        $filename = $directory . '/' . $serialNumber . '.svg';

        // Pastikan folder penampung sudah tersedia di storage/app/public
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Standard Performance: Gunakan kembali file jika file fisik sudah ada dan valid
        if (Storage::disk('public')->exists($filename)) {
            return $filename;
        }

        // Memproses enkapsulasi data nomor seri menjadi matriks gambar PNG tajam
        $qrCodeContent = QrCode::format('svg')
            ->size(250)
            ->margin(0)
            ->generate($serialNumber);

        // Simpan binary stream gambar ke disk
        Storage::disk('public')->put($filename, $qrCodeContent);

        return $filename;
    }

// Penghapusan file fisik QR Code lama dari storage disk. Dipanggil ketika terjadi perubahan nomor seri barang.
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

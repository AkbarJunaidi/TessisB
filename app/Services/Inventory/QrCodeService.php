<?php

namespace App\Services\Inventory;

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

    /**
     * Generate QR Code dari sebuah URL (dipakai untuk QR di Inventory
     * Report - isinya Signed URL ke halaman scan publik, BUKAN serial
     * number seperti generate() di atas).
     *
     * URL tidak aman dipakai langsung sebagai nama file (mengandung
     * karakter seperti /, ?, &, = dan bisa sangat panjang), jadi nama
     * filenya SENGAJA dipisah lewat parameter $safeKey (mis. ID barang),
     * bukan diturunkan dari isi $url itu sendiri.
     */
    public function generateFromUrl(string $url, string $safeKey): string
    {
        $directory = 'qrcodes';
        $filename = $directory . '/report-' . $safeKey . '.svg';

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Beda dengan generate(): TIDAK auto reuse file lama kalau sudah
        // ada, karena kalau App URL/domain berubah, isi QR lama (URL lama)
        // harus ikut ter-refresh. Signed URL yang dihasilkan tetap sama
        // isinya selama APP_KEY & domain tidak berubah, jadi regenerasi
        // ini murah/aman dipanggil berulang.
        $qrCodeContent = QrCode::format('svg')
            ->size(250)
            ->margin(0)
            ->generate($url);

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

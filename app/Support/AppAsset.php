<?php

namespace App\Support;

/**
 * Aset milik aplikasi di public/ (css/js). Versi dari waktu ubah file
 * supaya browser memuat ulang otomatis setelah file diganti.
 */
class AppAsset
{
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
    }
}

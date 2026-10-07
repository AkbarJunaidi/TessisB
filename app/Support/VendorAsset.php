<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Aset pihak ketiga: dipakai dari public/vendor bila sudah diunduh
 * (php artisan assets:vendor), jika belum otomatis memakai CDN.
 */
class VendorAsset
{
    private const MANIFEST = 'vendor/manifest.json';

    /** Daftar aset: url CDN, path lokal di public/, dan hash SRI CDN yang sudah dikenal (boleh null). */
    private const ASSETS = [
        'bootstrap-css' => [
            'cdn'       => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            'path'      => 'vendor/bootstrap/bootstrap.min.css',
            'integrity' => 'sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH',
        ],
        'bootstrap-js' => [
            'cdn'       => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            'path'      => 'vendor/bootstrap/bootstrap.bundle.min.js',
            'integrity' => 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz',
        ],
        'bootstrap-icons' => [
            'cdn'       => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            'path'      => 'vendor/bootstrap-icons/font/bootstrap-icons.min.css',
            'integrity' => 'sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+',
        ],
        'html5-qrcode' => [
            'cdn'       => 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js',
            'path'      => 'vendor/html5-qrcode/html5-qrcode.min.js',
            'integrity' => null,
        ],
        'chartjs' => [
            'cdn'       => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
            'path'      => 'vendor/chartjs/chart.umd.min.js',
            'integrity' => null,
        ],
    ];

    private static ?array $manifest = null;

    /** Semua definisi aset; dipakai perintah assets:vendor. */
    public static function definitions(): array
    {
        return self::ASSETS;
    }

    /** Tag <link> stylesheet untuk satu aset. */
    public static function style(string $key): HtmlString
    {
        return new HtmlString(sprintf('<link href="%s" rel="stylesheet"%s>', e(self::url($key)), self::securityAttributes($key)));
    }

    /** Tag <script> untuk satu aset. */
    public static function script(string $key): HtmlString
    {
        return new HtmlString(sprintf('<script src="%s"%s></script>', e(self::url($key)), self::securityAttributes($key)));
    }

    /** URL lokal bila file ada dan tercatat di manifest, selain itu URL CDN. */
    public static function url(string $key): string
    {
        $asset = self::ASSETS[$key];

        return self::isLocal($key) ? asset($asset['path']) : $asset['cdn'];
    }

    private static function isLocal(string $key): bool
    {
        return isset(self::manifest()[$key]) && is_file(public_path(self::ASSETS[$key]['path']));
    }

    /** Hash SRI: dari manifest untuk file lokal, dari daftar di atas untuk CDN. */
    private static function securityAttributes(string $key): string
    {
        if (self::isLocal($key)) {
            return ' integrity="' . e(self::manifest()[$key]['integrity']) . '"';
        }

        $hash = self::ASSETS[$key]['integrity'];

        return $hash ? ' integrity="' . e($hash) . '" crossorigin="anonymous"' : '';
    }

    /** Baca manifest sekali per request; kosong bila belum pernah mengunduh. */
    private static function manifest(): array
    {
        if (self::$manifest === null) {
            $file = public_path(self::MANIFEST);
            self::$manifest = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }

        return self::$manifest;
    }
}

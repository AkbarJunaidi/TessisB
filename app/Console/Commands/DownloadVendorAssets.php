<?php

namespace App\Console\Commands;

use App\Support\VendorAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadVendorAssets extends Command
{
    protected $signature = 'assets:vendor';

    protected $description = 'Unduh Bootstrap, Bootstrap Icons, html5-qrcode, dan Chart.js ke public/vendor (tanpa CDN)';

    /** Unduh semua aset, cocokkan hash SRI yang dikenal, lalu tulis manifest berisi hash tiap file. */
    public function handle(): int
    {
        $manifest = [];
        $failed = 0;

        foreach (VendorAsset::definitions() as $key => $asset) {
            $response = Http::timeout(60)->get($asset['cdn']);

            if (!$response->successful()) {
                $this->error("Gagal mengunduh {$key} ({$response->status()}).");
                $failed++;
                continue;
            }

            $body = $response->body();
            $hash = 'sha384-' . base64_encode(hash('sha384', $body, true));

            // Hash CDN yang sudah dikenal harus cocok; bila beda, file ditolak.
            if ($asset['integrity'] && $asset['integrity'] !== $hash) {
                $this->error("Hash {$key} tidak cocok, file ditolak.");
                $failed++;
                continue;
            }

            $target = public_path($asset['path']);
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $body);

            if ($key === 'bootstrap-icons' && !$this->downloadIconFonts($asset, $body)) {
                $failed++;
                continue;
            }

            $manifest[$key] = ['integrity' => $hash];
            $this->info("OK {$key}");
        }

        File::ensureDirectoryExists(public_path('vendor'));
        File::put(public_path('vendor/manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** Bootstrap Icons memanggil file font relatif; unduh semuanya ke folder fonts di sebelah CSS. */
    private function downloadIconFonts(array $asset, string $css): bool
    {
        preg_match_all('#url\("\./(fonts/[^"?]+)#', $css, $matches);

        foreach (array_unique($matches[1]) as $relative) {
            $response = Http::timeout(60)->get(dirname($asset['cdn']) . '/' . $relative);

            if (!$response->successful()) {
                $this->error("Gagal mengunduh font {$relative}.");
                return false;
            }

            $path = public_path(dirname($asset['path']) . '/' . $relative);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $response->body());
        }

        return true;
    }
}

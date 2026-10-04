<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan aplikasi (key-value) yang bisa diubah pemilik dari halaman Pengaturan.
 * Nilai yang belum pernah disimpan jatuh ke config/app_settings.php.
 */
class AppSetting extends Model
{
    private const CACHE_KEY = 'app_settings.all';

    protected $fillable = ['key', 'value'];

    private static ?array $memo = null;

    /** Semua nilai tersimpan; kosong bila tabel belum dimigrasi supaya aplikasi tidak error. */
    private static function stored(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            self::$memo = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            self::$memo = [];
        }

        return self::$memo;
    }

    public static function flush(): void
    {
        self::$memo = null;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
            // Cache tidak tersedia: nilai dibaca ulang dari database pada request berikutnya
        }
    }

    /** Nilai tersimpan menang walau kosong (pemilik sengaja mengosongkan); jika belum ada pakai bawaan. */
    public static function get(string $key): string
    {
        $stored = self::stored();

        if (array_key_exists($key, $stored)) {
            return (string) $stored[$key];
        }

        $default = config("app_settings.defaults.{$key}");

        return is_array($default) ? '' : (string) $default;
    }

    public static function int(string $key): int
    {
        $value = self::get($key);

        return $value === '' ? (int) config("app_settings.defaults.{$key}") : (int) $value;
    }

    /**
     * Profil perusahaan untuk header/footer dokumen dan tampilan.
     *
     * @return array<string, mixed>
     */
    public static function company(): array
    {
        $c = [
            'name'           => self::get('company_name'),
            'tagline'        => self::get('company_tagline'),
            'address'        => self::get('company_address'),
            'phone'          => self::get('company_phone'),
            'whatsapp'       => self::get('company_whatsapp'),
            'footer_tagline' => self::get('company_footer_tagline'),
            'footer_contact' => self::get('company_footer_contact'),
            'website'        => self::get('company_website'),
            'instagram'      => self::get('company_instagram'),
            'tiktok'         => self::get('company_tiktok'),
        ];

        // Baris alamat panjang (Surat Jalan, Laporan Keuangan) dan ringkas (Kwitansi), bagian kosong dilewati.
        $c['address_line'] = collect([
            $c['address'] !== '' ? 'Alamat : ' . $c['address'] : null,
            $c['phone'] !== '' ? 'Telp : ' . $c['phone'] : null,
            $c['whatsapp'] !== '' ? 'Whatsapp : ' . $c['whatsapp'] : null,
        ])->filter()->implode(', ');

        $c['contact_line'] = collect([
            $c['address'],
            collect([$c['phone'], $c['whatsapp']])->filter()->implode(' / '),
        ])->filter()->all();

        $c['footer_parts'] = collect([$c['footer_contact'], $c['website'], $c['instagram'], $c['tiktok']])
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        return $c;
    }

    /** @return array<int, string> */
    public static function projectCategories(): array
    {
        $stored = self::stored();

        if (array_key_exists('project_categories', $stored)) {
            $list = json_decode((string) $stored['project_categories'], true);

            if (is_array($list) && $list !== []) {
                return array_values($list);
            }
        }

        return config('app_settings.defaults.project_categories');
    }

    /** @return array<int, array{name: string, color: string}> status barang kustom */
    public static function inventoryStatuses(): array
    {
        $list = json_decode(self::stored()['inventory_statuses'] ?? '[]', true);

        return is_array($list) ? array_values($list) : [];
    }

    /** @return array<int, array{name: string, type: string}> kategori transaksi keuangan kustom */
    public static function financeCategories(): array
    {
        $list = json_decode(self::stored()['finance_categories'] ?? '[]', true);

        return is_array($list) ? array_values($list) : [];
    }

    /** @return array<int, string> */
    public static function allowedExtensions(): array
    {
        return array_values(array_filter(explode(',', self::get('upload_allowed_extensions'))));
    }

    /** Path absolut gambar kustom bila ada, selain itu file bawaan di folder public. */
    public static function imagePath(string $key): string
    {
        $custom = self::get('image_' . $key);

        if ($custom !== '' && Storage::disk('public')->exists($custom)) {
            return Storage::disk('public')->path($custom);
        }

        return public_path(config("app_settings.images.{$key}"));
    }

    /** URL gambar untuk ditampilkan di browser (kustom atau bawaan). */
    public static function imageUrl(string $key): string
    {
        $custom = self::get('image_' . $key);

        if ($custom !== '' && Storage::disk('public')->exists($custom)) {
            return asset('storage/' . $custom);
        }

        return asset(config("app_settings.images.{$key}"));
    }

    public static function hasCustomImage(string $key): bool
    {
        $custom = self::get('image_' . $key);

        return $custom !== '' && Storage::disk('public')->exists($custom);
    }
}

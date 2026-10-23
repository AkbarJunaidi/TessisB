<?php

namespace App\Support;

use Closure;

/**
 * Batas panjang teks input: satu sumber untuk validasi backend dan atribut maxlength di view.
 * Angkanya mengikuti tata letak PDF Inventaris (muat 1 halaman) dan isi QR serial.
 */
class TextLimits
{
    public const ITEM_NAME = 50;      // Nama barang (Inventaris dan item Pembelian)
    public const BRAND = 40;
    public const ATTR_NAME = 30;      // Nama informasi tambahan barang
    public const ATTR_VALUE = 60;
    public const SERIAL = 50;         // Juga isi QR; makin panjang makin padat dan sulit dipindai
    public const LOCATION_NAME = 60;
    public const ENTITY_NAME = 100;   // Nama orang/instansi: Kontak, Client, PIC, User, judul Task
    public const COMPANY_NAME = 60;

    /**
     * Aturan maksimal karakter. Nilai lama yang sudah tersimpan dan lebih panjang boleh tetap selama tidak diubah.
     *
     * @param  array<int, mixed>|string|null  $legacy  nilai tersimpan yang tetap diizinkan
     */
    public static function max(int $limit, string $label, array|string|null $legacy = null): Closure
    {
        $allowed = array_map('strval', array_filter((array) $legacy, fn ($value) => $value !== null));

        return function (string $attribute, mixed $value, Closure $fail) use ($limit, $label, $allowed) {
            if (mb_strlen((string) $value) > $limit && !in_array((string) $value, $allowed, true)) {
                $fail("{$label} maksimal {$limit} karakter.");
            }
        };
    }
}

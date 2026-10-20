<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Batas angka input (uang, jumlah, stok, durasi). Nilainya diatur di Pengaturan, dipakai backend dan view.
 */
class Limits
{
    public const KEYS = ['money_max', 'qty_max', 'stock_max', 'duration_max'];

    public static function money(): int
    {
        return self::value('money_max');
    }

    public static function qty(): int
    {
        return self::value('qty_max');
    }

    public static function stock(): int
    {
        return self::value('stock_max');
    }

    public static function duration(): int
    {
        return self::value('duration_max');
    }

    /** @return array{0: int, 1: int} rentang yang boleh diatur: [terendah, tertinggi] */
    public static function range(string $key): array
    {
        return config("limits.range.{$key}");
    }

    /** Dijepit ke rentang, jadi nilai tersimpan yang rusak tidak bisa membuat batas jadi 0 atau melewati kolom database. */
    private static function value(string $key): int
    {
        [$min, $max] = self::range($key);

        return max($min, min($max, AppSetting::int('limit_' . $key)));
    }
}

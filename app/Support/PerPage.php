<?php

namespace App\Support;

/**
 * Jumlah baris per halaman untuk semua tabel berpagination.
 * Nilai dibaca dari ?per_page= dan hanya diterima bila ada di OPTIONS.
 */
class PerPage
{
    public const OPTIONS = [10, 25, 50, 100];

    public static function resolve(int $default = 10): int
    {
        $value = (int) request()->query('per_page');

        return in_array($value, self::OPTIONS, true) ? $value : $default;
    }
}

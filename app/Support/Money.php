<?php

namespace App\Support;

class Money
{
    /**
     * Format angka jadi Rupiah Indonesia, contoh: 15000000 -> "Rp 15.000.000".
     */
    public static function formatRupiah(int|float|string|null $amount): string
    {
        return 'Rp ' . number_format((float) ($amount ?? 0), 0, ',', '.');
    }
}

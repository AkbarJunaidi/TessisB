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

    /**
     * Rupiah ringkas untuk ruang sempit, contoh: 12500000 -> "Rp 12,5 jt".
     */
    public static function compact(int|float|string|null $amount): string
    {
        $number = (float) ($amount ?? 0);
        $abs    = abs($number);
        $sign   = $number < 0 ? '-' : '';

        foreach ([1000000000 => ' M', 1000000 => ' jt', 1000 => ' rb'] as $size => $suffix) {
            if ($abs >= $size) {
                return $sign . 'Rp ' . rtrim(rtrim(number_format($abs / $size, 1, ',', '.'), '0'), ',') . $suffix;
            }
        }

        return $sign . 'Rp ' . number_format($abs, 0, ',', '.');
    }
}

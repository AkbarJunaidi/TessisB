<?php

namespace App\Support;

/**
 * Konversi nominal ke teks Bahasa Indonesia (baris "Terbilang" di
 * kwitansi) - algoritma rekursif standar, dipecah per skala (puluhan,
 * ratusan, ribuan, dst).
 */
class Terbilang
{
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    public static function make(int|float|string $amount): string
    {
        $number = (int) round((float) $amount);

        if ($number === 0) {
            return 'Nol rupiah';
        }

        if ($number < 0) {
            return 'Minus ' . self::make(-$number);
        }

        return ucfirst(self::convert($number)) . ' rupiah';
    }

    private static function convert(int $number): string
    {
        if ($number < 12) {
            return self::SATUAN[$number];
        }

        if ($number < 20) {
            return trim(self::convert($number - 10) . ' belas');
        }

        if ($number < 100) {
            return trim(self::convert(intdiv($number, 10)) . ' puluh ' . self::convert($number % 10));
        }

        if ($number < 200) {
            return trim('seratus ' . self::convert($number - 100));
        }

        if ($number < 1000) {
            return trim(self::convert(intdiv($number, 100)) . ' ratus ' . self::convert($number % 100));
        }

        if ($number < 2000) {
            return trim('seribu ' . self::convert($number - 1000));
        }

        if ($number < 1_000_000) {
            return trim(self::convert(intdiv($number, 1000)) . ' ribu ' . self::convert($number % 1000));
        }

        if ($number < 1_000_000_000) {
            return trim(self::convert(intdiv($number, 1_000_000)) . ' juta ' . self::convert($number % 1_000_000));
        }

        if ($number < 1_000_000_000_000) {
            return trim(self::convert(intdiv($number, 1_000_000_000)) . ' miliar ' . self::convert($number % 1_000_000_000));
        }

        return trim(self::convert(intdiv($number, 1_000_000_000_000)) . ' triliun ' . self::convert($number % 1_000_000_000_000));
    }
}

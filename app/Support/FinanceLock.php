<?php

namespace App\Support;

use App\Models\AppSetting;
use Carbon\Carbon;
use Exception;

/**
 * Tutup buku: transaksi bertanggal sampai tanggal kunci (Pengaturan) tidak boleh dibuat, diubah, atau dihapus.
 */
class FinanceLock
{
    public static function date(): ?Carbon
    {
        $value = AppSetting::get('finance_lock_date');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isLocked(Carbon|string|null $date): bool
    {
        $lock = self::date();

        if (!$lock || $date === null || $date === '') {
            return false;
        }

        return Carbon::parse($date)->startOfDay()->lte($lock);
    }

    /** @throws Exception */
    public static function assertOpen(Carbon|string|null $date): void
    {
        if (self::isLocked($date)) {
            throw new Exception('Periode keuangan sampai ' . self::date()->format('d/m/Y') . ' sudah ditutup, transaksi bertanggal sebelum atau pada tanggal itu tidak bisa diubah.');
        }
    }
}

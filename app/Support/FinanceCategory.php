<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Kategori transaksi keuangan: kategori sistem (tetap) ditambah kategori kustom dari Pengaturan.
 * "Pembelian" dan "Servis Alat" hanya dibuat otomatis oleh modulnya, tidak bisa dipilih manual.
 */
class FinanceCategory
{
    public const PROJECT_INCOME  = 'Pendapatan Project';
    public const PROJECT_EXPENSE = 'Biaya Project';
    public const PURCHASE        = 'Pembelian';
    public const REPAIR          = 'Servis Alat';

    public const SYSTEM = [
        'Pendapatan Project' => 'income',
        'Pendapatan Lain'    => 'income',
        'Biaya Project'      => 'expense',
        'Pembelian'          => 'expense',
        'Servis Alat'        => 'expense',
        'Gaji'               => 'expense',
        'Honor Kru'          => 'expense',
        'Operasional'        => 'expense',
        'Lainnya'            => 'expense',
    ];

    public const AUTO = [self::PURCHASE, self::REPAIR];

    public const TYPES = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];

    /** @return array<int, array{name: string, type: string}> */
    public static function custom(): array
    {
        return AppSetting::financeCategories();
    }

    /** @return array<string, string> nama => tipe (income|expense) */
    public static function map(): array
    {
        $map = self::SYSTEM;

        foreach (self::custom() as $row) {
            $map[$row['name']] = $row['type'];
        }

        return $map;
    }

    /** @return array<int, string> */
    public static function all(?string $type = null): array
    {
        $map = self::map();

        return array_keys($type ? array_filter($map, fn ($t) => $t === $type) : $map);
    }

    public static function type(string $name): ?string
    {
        return self::map()[$name] ?? null;
    }

    /** Kategori yang boleh dipilih saat input manual. @return array<int, string> */
    public static function manual(?string $type = null): array
    {
        return array_values(array_diff(self::all($type), self::AUTO));
    }
}

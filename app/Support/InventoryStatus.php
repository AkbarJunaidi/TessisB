<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Daftar status barang: 5 status sistem (dipakai logika Surat Jalan, Perbaikan, dan Scan, tidak bisa diubah)
 * ditambah status kustom dari Pengaturan. Status kustom selalu berarti "tidak tersedia dipinjam".
 */
class InventoryStatus
{
    public const SYSTEM = [
        'Tersedia'  => 'success',
        'Dipinjam'  => 'primary',
        'Perbaikan' => 'warning',
        'Rusak'     => 'danger',
        'Hilang'    => 'secondary',
    ];

    /** Warna yang boleh dipilih untuk status kustom (semua punya kelas badge di tema). */
    public const COLORS = [
        'primary'   => 'Biru',
        'info'      => 'Biru muda',
        'warning'   => 'Kuning',
        'danger'    => 'Merah',
        'secondary' => 'Abu-abu',
        'success'   => 'Hijau',
    ];

    /** Nama yang tidak boleh dipakai status kustom (status sistem dan nilai filter). */
    public const RESERVED = ['semua status', 'dikembalikan'];

    /** @return array<int, array{name: string, color: string}> */
    public static function custom(): array
    {
        return AppSetting::inventoryStatuses();
    }

    /** @return array<string, string> nama => warna, status sistem lebih dulu. */
    public static function colors(): array
    {
        $map = self::SYSTEM;

        foreach (self::custom() as $row) {
            $map[$row['name']] = $row['color'];
        }

        return $map;
    }

    /** Semua nama status (dipakai field status tingkat barang dan filter). @return array<int, string> */
    public static function all(): array
    {
        return array_keys(self::colors());
    }

    /** Status yang boleh dipilih manual untuk satu unit (Dipinjam turunan dari Surat Jalan). @return array<int, string> */
    public static function assignable(): array
    {
        return array_values(array_diff(self::all(), ['Dipinjam']));
    }

    public static function color(?string $name): string
    {
        return self::colors()[$name] ?? 'secondary';
    }

    /** Kelas badge bergaya "subtle" (detail, edit, scan). */
    public static function subtleClass(?string $name, bool $withBorderClass = true): string
    {
        $c = self::color($name);

        return "bg-{$c}-subtle text-{$c} " . ($withBorderClass ? "border border-{$c}-subtle" : "border-{$c}-subtle");
    }

    /** Kelas badge bergaya "soft" (tabel daftar). */
    public static function softClass(?string $name): string
    {
        return 'badge-soft-' . self::color($name);
    }
}

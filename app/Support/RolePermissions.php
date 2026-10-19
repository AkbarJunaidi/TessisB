<?php

namespace App\Support;

use App\Models\CustomRole;
use Throwable;

/**
 * Izin bawaan per role. Satu pintu supaya User, UserService, dan form
 * Edit User memakai nilai yang sama. Role bawaan dari config/permissions.php,
 * role kustom dari tabel custom_roles (diatur Super Admin).
 */
class RolePermissions
{
    private const BUILTIN_LABELS = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'employee'    => 'Employee',
    ];

    /** @var array<string, array{name: string, base_role: string, permissions: array}>|null */
    private static ?array $custom = null;

    /** Izin bawaan satu role, sudah termasuk aturan turunan Scan. */
    public static function defaultsFor(string $role): array
    {
        $builtin = config("permissions.role_defaults.{$role}");

        return self::withScanDefault($builtin ?? (self::custom()[$role]['permissions'] ?? []));
    }

    /** Izin bawaan semua role, bawaan dan kustom. */
    public static function all(): array
    {
        $all = array_map([self::class, 'withScanDefault'], config('permissions.role_defaults', []));

        foreach (self::custom() as $slug => $role) {
            $all[$slug] = self::withScanDefault($role['permissions']);
        }

        return $all;
    }

    /** Role dasar yang menentukan halaman dan notifikasi tingkat role. Role tak dikenal dianggap employee. */
    public static function baseRoleOf(?string $role): string
    {
        if (isset(self::BUILTIN_LABELS[$role])) {
            return $role;
        }

        return self::custom()[$role]['base_role'] ?? 'employee';
    }

    public static function label(?string $role): string
    {
        return self::BUILTIN_LABELS[$role] ?? (self::custom()[$role]['name'] ?? ucwords(str_replace('_', ' ', (string) $role)));
    }

    /** Semua kunci role yang sah: bawaan dan kustom. */
    public static function slugs(): array
    {
        return array_merge(array_keys(self::BUILTIN_LABELS), array_keys(self::custom()));
    }

    public static function isValid(?string $role): bool
    {
        return in_array($role, self::slugs(), true);
    }

    /** Dipanggil setelah role kustom berubah supaya pengecekan berikutnya membaca data terbaru. */
    public static function flush(): void
    {
        self::$custom = null;
    }

    /**
     * @return array<string, array{name: string, base_role: string, permissions: array}>
     */
    private static function custom(): array
    {
        if (self::$custom === null) {
            try {
                self::$custom = CustomRole::query()->get()->mapWithKeys(fn (CustomRole $role) => [
                    $role->slug => [
                        'name'        => $role->name,
                        'base_role'   => $role->base_role,
                        'permissions' => $role->permissions ?? [],
                    ],
                ])->all();
            } catch (Throwable) {
                // Tabel belum dimigrasi (mis. saat deploy): role kustom dianggap belum ada.
                self::$custom = [];
            }
        }

        return self::$custom;
    }

    /** Scan aktif bila ada akses Inventory atau Project; Super Admin bisa menimpanya lewat override. */
    private static function withScanDefault(array $permissions): array
    {
        $permissions['scan_barang']['view'] = (bool) (
            data_get($permissions, 'inventory.view')
            || data_get($permissions, 'tracking_progress.view')
        );

        return $permissions;
    }
}

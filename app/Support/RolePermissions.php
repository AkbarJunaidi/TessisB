<?php

namespace App\Support;

/**
 * Izin bawaan per role. Satu pintu supaya User, UserService, dan form
 * Edit User memakai nilai yang sama.
 */
class RolePermissions
{
    /** Izin bawaan satu role, sudah termasuk aturan turunan Scan. */
    public static function defaultsFor(string $role): array
    {
        return self::withScanDefault(config("permissions.role_defaults.{$role}", []));
    }

    /** Izin bawaan semua role. */
    public static function all(): array
    {
        return array_map([self::class, 'withScanDefault'], config('permissions.role_defaults', []));
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

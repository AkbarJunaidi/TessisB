<?php

namespace App\Services\Role;

use App\Models\CustomRole;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use App\Support\RolePermissions;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RoleService
{
    private const LOG_MODULE = 'Role Management';

    public function __construct(protected ActivityLogService $activityLogService)
    {
    }

    /** Role kustom beserta jumlah pengguna dan ringkasan izinnya. */
    public function all(): Collection
    {
        return CustomRole::withCount('users')->orderBy('name')->get();
    }

    /** Jumlah pengguna per role bawaan. */
    public function builtinCounts(): array
    {
        return User::query()
            ->selectRaw('role, count(*) as total')
            ->whereIn('role', CustomRole::RESERVED)
            ->groupBy('role')
            ->pluck('total', 'role')
            ->all();
    }

    public function create(array $data): CustomRole
    {
        $role = CustomRole::create([
            'name'        => trim($data['name']),
            'slug'        => CustomRole::makeSlug($data['name']),
            'base_role'   => $data['base_role'],
            'description' => $data['description'] ?? null,
            'permissions' => $this->normalizePermissions($data['permissions'] ?? []),
        ]);

        RolePermissions::flush();
        $this->log("Membuat role \"{$role->name}\"");

        return $role;
    }

    public function update(CustomRole $role, array $data): CustomRole
    {
        $role->update([
            'name'        => trim($data['name']),
            'base_role'   => $data['base_role'],
            'description' => $data['description'] ?? null,
            'permissions' => $this->normalizePermissions($data['permissions'] ?? []),
        ]);

        RolePermissions::flush();
        $this->log("Mengubah role \"{$role->name}\"");

        return $role;
    }

    /** Role yang masih dipakai pengguna tidak bisa dihapus supaya tidak ada akun tanpa role. */
    public function delete(CustomRole $role): void
    {
        $count = User::where('role', $role->slug)->count();

        if ($count > 0) {
            throw new Exception("Role \"{$role->name}\" masih dipakai {$count} user. Pindahkan user tersebut ke role lain dulu.");
        }

        $name = $role->name;
        $role->delete();

        RolePermissions::flush();
        $this->log("Menghapus role \"{$name}\"");
    }

    /**
     * Izin tersimpan lengkap untuk semua modul dan aksi di katalog (true/false),
     * jadi aksi yang tidak dicentang jelas ditolak dan aksi baru di katalog default tertutup.
     */
    private function normalizePermissions(array $submitted): array
    {
        $normalized = [];

        foreach (config('permissions.modules', []) as $module => $config) {
            foreach (array_keys($config['actions']) as $action) {
                $normalized[$module][$action] = (bool) data_get($submitted, "{$module}.{$action}", false);
            }
        }

        return $normalized;
    }

    private function log(string $message): void
    {
        $this->activityLogService->log(Auth::id(), self::LOG_MODULE, $message);
    }
}

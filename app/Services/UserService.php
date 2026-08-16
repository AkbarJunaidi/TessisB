<?php

namespace App\Services;

use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class UserService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Mengambil seluruh data user dengan paginasi.
     */
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
    }

    /**
     * Menambahkan user baru.
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'],
            'status'   => $data['status'],
            'permission_overrides' => $this->resolvePermissionOverrides(
                $data['role'],
                $data['permissions'] ?? []
            ),
        ]);

        $this->activityLogService->log(
            Auth::id(),
            'User Management',
            'Create User'
        );

        return $user;
    }

    /**
     * Memperbarui data user.
     *
     * Role & permission_overrides HANYA diproses jika yang melakukan aksi ini
     * (Auth::user()) adalah Super Admin - meskipun route/permission
     * 'edit_user' sudah didelegasikan ke Admin, field role & hak akses tetap
     * diabaikan dari input Admin. Ini mencegah Admin memberi dirinya sendiri
     * (lewat akun user lain) hak akses lebih tinggi atau menaikkan role user
     * manapun menjadi Super Admin lewat form Edit User.
     */
    public function updateUser(User $user, array $data): User
    {
        $actorIsSuperAdmin = Auth::user()?->isSuperAdmin() ?? false;

        // Admin yang diberi permission 'edit_user' tetap tidak boleh mengubah
        // (termasuk menonaktifkan) akun Super Admin manapun.
        if (!$actorIsSuperAdmin && $user->isSuperAdmin()) {
            throw new Exception(
                'Anda tidak memiliki hak akses untuk mengubah akun Super Admin.'
            );
        }

        if (
            $user->id === Auth::id() &&
            $actorIsSuperAdmin &&
            $user->role !== $data['role']
        ) {
            throw new Exception(
                'Anda tidak dapat mengubah role akun sendiri.'
            );
        }

        if (
            $user->id === Auth::id() &&
            $data['status'] === 'inactive'
        ) {
            throw new Exception(
                'Anda tidak dapat menonaktifkan akun sendiri.'
            );
        }

        $payload = [
            'name'   => $data['name'],
            'email'  => $data['email'],
            'status' => $data['status'],
        ];

        if ($actorIsSuperAdmin) {
            $payload['role'] = $data['role'];
            $payload['permission_overrides'] = $this->resolvePermissionOverrides(
                $data['role'],
                $data['permissions'] ?? []
            );
        }

        $user->update($payload);

        if (!empty($data['password'])) {
            $user->update([
                'password' => $data['password'],
            ]);
        }

        $this->activityLogService->log(
            Auth::id(),
            'User Management',
            'Update User'
        );

        return $user->fresh();
    }

    /**
     * Reset password user.
     */
    public function resetPassword(User $user): bool
    {
        $updated = $user->update([
            'password' => 'Password123!',
        ]);

        if ($updated) {
            $this->activityLogService->log(
                Auth::id(),
                'User Management',
                'Reset Password'
            );
        }

        return $updated;
    }

    /**
     * Mengubah status user.
     */
    public function changeStatus(User $user, string $status): bool
    {
        if (
            $user->id === Auth::id() &&
            $status === 'inactive'
        ) {
            throw new Exception(
                'Anda tidak dapat menonaktifkan akun sendiri.'
            );
        }

        $updated = $user->update([
            'status' => $status,
        ]);

        if ($updated) {
            $this->activityLogService->log(
                Auth::id(),
                'User Management',
                'Change Status'
            );
        }

        return $updated;
    }

    /**
     * Mengubah role user.
     */
    public function changeRole(User $user, string $role): bool
    {
        if ($user->id === Auth::id()) {
            throw new Exception(
                'Anda tidak dapat mengubah role akun sendiri.'
            );
        }

        $updated = $user->update([
            'role' => $role,
            // Role berubah -> permission override lama sudah tidak relevan,
            // kembalikan mengikuti default Role yang baru.
            'permission_overrides' => null,
        ]);

        if ($updated) {
            $this->activityLogService->log(
                Auth::id(),
                'User Management',
                'Change Role'
            );
        }

        return $updated;
    }

    /**
     * Mengambil default permission untuk sebuah Role
     * dari config/permissions.php.
     */
    public function getDefaultPermissions(string $role): array
    {
        return config("permissions.role_defaults.{$role}", []);
    }

    /**
     * Membandingkan permission yang disubmit dengan default Role.
     * Jika identik -> simpan null (user mengikuti default Role).
     * Jika berbeda -> simpan sebagai override (custom aktif).
     *
     * $submitted diharapkan berbentuk:
     * ['inventory' => ['view' => true, 'create' => false, ...], ...]
     */
    public function resolvePermissionOverrides(string $role, array $submitted): ?array
    {
        $catalog = config('permissions.modules', []);
        $default = $this->getDefaultPermissions($role);
        $normalized = [];

        foreach ($catalog as $module => $config) {
            foreach (array_keys($config['actions']) as $action) {
                $normalized[$module][$action] = (bool) data_get(
                    $submitted,
                    "{$module}.{$action}",
                    false
                );
            }
        }

        return $normalized === $default ? null : $normalized;
    }

    /**
     * Ringkasan status permission per modul, dipakai pada Card
     * "Ringkasan Hak Akses" di halaman Edit/Create User.
     *
     * Status per modul:
     * - "Tidak Diakses" -> tidak ada aksi yang aktif
     * - "Read Only"      -> hanya aksi 'view'/'view_user' yang aktif
     * - "Diberikan"      -> ada aksi lain (bukan hanya view) yang aktif
     */
    public function buildPermissionSummary(array $permissions): array
    {
        $summary = ['granted' => 0, 'read_only' => 0, 'no_access' => 0];

        foreach (config('permissions.modules', []) as $module => $config) {
            $moduleActions = $permissions[$module] ?? [];
            $activeActions = array_keys(array_filter($moduleActions));

            if (empty($activeActions)) {
                $summary['no_access']++;
                continue;
            }

            $viewOnly = count($activeActions) === 1 &&
                in_array($activeActions[0], ['view', 'view_user'], true);

            $viewOnly ? $summary['read_only']++ : $summary['granted']++;
        }

        return $summary;
    }

    /**
     * Menghapus user (Soft Delete).
     */
    public function deleteUser(User $user): bool
    {
        if ($user->id === Auth::id()) {
            throw new Exception(
                'Anda tidak dapat menghapus akun sendiri.'
            );
        }

        // Admin yang diberi permission 'delete_user' tetap tidak boleh
        // menghapus akun Super Admin manapun.
        if (!(Auth::user()?->isSuperAdmin() ?? false) && $user->isSuperAdmin()) {
            throw new Exception(
                'Anda tidak memiliki hak akses untuk menghapus akun Super Admin.'
            );
        }

        $deleted = $user->delete();

        if ($deleted) {
            $this->activityLogService->log(
                Auth::id(),
                'User Management',
                'Delete User'
            );
        }

        return $deleted;
    }
}


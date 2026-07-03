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
     */
    public function updateUser(User $user, array $data): User
    {
        if (
            $user->id === Auth::id() &&
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

        $user->update([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'role'   => $data['role'],
            'status' => $data['status'],
        ]);

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
     * Menghapus user (Soft Delete).
     */
    public function deleteUser(User $user): bool
    {
        if ($user->id === Auth::id()) {
            throw new Exception(
                'Anda tidak dapat menghapus akun sendiri.'
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


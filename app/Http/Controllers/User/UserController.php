<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    protected UserService $userService;

// Dependency Injection.
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

// Menampilkan daftar user.
    public function index(): View
    {
        abort_unless(
            Auth::user()?->hasPermission('user_management', 'view_user'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data user.'
        );

        $users = $this->userService->getAllPaginated(10);

        return view('user.index', compact('users'));
    }

//  Menampilkan form tambah user.
    public function create(): View
    {
        return view('user.create', $this->buildPermissionViewData());
    }

// Menyimpan user baru.
    public function store(UserRequest $request): RedirectResponse
    {
        try {

            $this->userService->createUser(
                $request->validated()
            );

            return redirect()
                ->route('users.index')
                ->with('success', 'User berhasil ditambahkan.');

        } catch (Exception $e) {

            return back()
                ->withInput()
                ->with('error', $e->getMessage());

        }
    }

// Menampilkan detail user.
    public function show(User $user): View
    {
        abort_unless(
            Auth::user()?->hasPermission('user_management', 'view_user'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data user.'
        );

        return view('user.show', compact('user'));
    }

// Menampilkan form edit user.
    public function edit(User $user): View
    {
        abort_unless(
            Auth::user()?->hasPermission('user_management', 'edit_user'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data user.'
        );

        return view('user.edit', array_merge(
            compact('user'),
            $this->buildPermissionViewData($user)
        ));
    }

// Memperbarui data user.
    public function update(
        UserRequest $request,
        User $user
    ): RedirectResponse {

        abort_unless(
            Auth::user()?->hasPermission('user_management', 'edit_user'),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data user.'
        );

        try {

            $this->userService->updateUser(
                $user,
                $request->validated()
            );

            return redirect()
                ->route('users.index')
                ->with('success', 'User berhasil diperbarui.');

        } catch (Exception $e) {

            return back()
                ->withInput()
                ->with('error', $e->getMessage());

        }
    }

// Reset password user.
    public function resetPassword(User $user): RedirectResponse
    {
        try {

            $this->userService->resetPassword($user);

            return back()->with(
                'success',
                'Password berhasil direset.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );

        }
    }

// Mengubah status user.
    public function changeStatus(
        User $user
    ): RedirectResponse {

        try {

            $status = $user->status === 'active'
                ? 'inactive'
                : 'active';

            $this->userService->changeStatus(
                $user,
                $status
            );

            return back()->with(
                'success',
                'Status user berhasil diperbarui.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );

        }
    }

//  Mengubah role user
    public function changeRole(
        User $user
    ): RedirectResponse {

        try {

            $role = request('role');

            $this->userService->changeRole(
                $user,
                $role
            );

            return back()->with(
                'success',
                'Role user berhasil diperbarui.'
            );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );

        }
    }

    /**
     * Menyiapkan data katalog permission, default per Role,
     * permission efektif, dan ringkasan untuk Card "Hak Akses Pengguna".
     */
    private function buildPermissionViewData(?User $user = null): array
    {
        $catalog = config('permissions.modules', []);
        $roleDefaults = config('permissions.role_defaults', []);

        $effectivePermissions = $user
            ? $user->getEffectivePermissions()
            : ($roleDefaults['employee'] ?? []);

        return [
            'permissionCatalog'    => $catalog,
            'roleDefaults'         => $roleDefaults,
            'effectivePermissions' => $effectivePermissions,
            'permissionSummary'    => $this->userService->buildPermissionSummary($effectivePermissions),
            'hasCustomPermission'  => $user?->hasCustomPermission() ?? false,
        ];
    }

    /**
     * Menghapus user.
     */
    public function destroy(
        User $user
    ): RedirectResponse {

        abort_unless(
            Auth::user()?->hasPermission('user_management', 'delete_user'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus user.'
        );

        try {

            $this->userService->deleteUser($user);

            return redirect()
                ->route('users.index')
                ->with(
                    'success',
                    'User berhasil dihapus.'
                );

        } catch (Exception $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );

        }
    }
}


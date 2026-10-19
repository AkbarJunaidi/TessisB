<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Models\CustomRole;
use App\Services\Role\RoleService;
use App\Support\RolePermissions;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService)
    {
    }

    public function index()
    {
        $this->authorizeSuperAdmin();

        return view('role.index', [
            'roles'         => $this->roleService->all(),
            'builtinCounts' => $this->roleService->builtinCounts(),
        ]);
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('role.create', $this->formData(new CustomRole(['base_role' => 'employee', 'permissions' => []])));
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        try {
            $role = $this->roleService->create($request->validated());

            return redirect()->route('roles.index')->with('success', "Role \"{$role->name}\" berhasil dibuat.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(CustomRole $role)
    {
        $this->authorizeSuperAdmin();

        return view('role.edit', $this->formData($role));
    }

    public function update(RoleRequest $request, CustomRole $role): RedirectResponse
    {
        try {
            $this->roleService->update($role, $request->validated());

            return redirect()->route('roles.index')->with('success', "Role \"{$role->name}\" berhasil diperbarui.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(CustomRole $role): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        try {
            $this->roleService->delete($role);

            return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403, 'Hanya Super Admin yang dapat mengatur role.');
    }

    /**
     * Katalog izin dan bawaan semua role (untuk tombol "Salin dari role"), serta izin terpilih saat ini.
     */
    private function formData(CustomRole $role): array
    {
        $selected = $role->permissions ?? [];
        $roleDefaults = RolePermissions::all();

        return [
            'role'              => $role,
            'permissionCatalog' => config('permissions.modules', []),
            'roleDefaults'      => $roleDefaults,
            'selected'          => $selected,
            'copySources'       => collect(['admin' => 'Admin', 'employee' => 'Employee'])
                ->union(CustomRole::query()->where('id', '!=', $role->id ?? 0)->orderBy('name')->pluck('name', 'slug'))
                ->all(),
        ];
    }
}

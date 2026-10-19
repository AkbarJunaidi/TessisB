<?php

namespace App\Http\Requests\Role;

use App\Models\CustomRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Mengatur role menentukan hak akses semua orang, jadi hanya Super Admin.
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('custom_roles', 'name')->ignore($role),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $reserved = ['super admin', 'super_admin', 'admin', 'employee'];

                    if (in_array(mb_strtolower(trim($value)), $reserved, true)) {
                        $fail('Nama ini dipakai role bawaan. Pilih nama lain.');
                    }
                },
            ],
            'base_role'   => ['required', Rule::in(array_keys(CustomRole::BASE_ROLES))],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Nama role wajib diisi.',
            'name.unique'     => 'Nama role ini sudah dipakai.',
            'base_role.required' => 'Tingkat dasar wajib dipilih.',
            'base_role.in'    => 'Tingkat dasar tidak valid.',
        ];
    }
}

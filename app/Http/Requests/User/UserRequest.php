<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan mengirim request ini.
     */
    public function authorize(): bool
    {
        $action = $this->route()?->getActionMethod() === 'update' ? 'edit_user' : 'create_user';

        return $this->user()?->hasPermission('user_management', $action) ?? false;
    }

    /**
     * Aturan validasi data User.
     */
    public function rules(): array
    {
        $user = $this->route('user');

        $rules = [

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in([
                    'super_admin',
                    'admin',
                    'employee',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'permissions' => [
                'nullable',
                'array',
            ],
        ];

        // Whitelist key module & aksi sesuai katalog config/permissions.php,
        // supaya tidak ada key permission yang tidak dikenal ikut tersimpan.
        foreach (config('permissions.modules', []) as $module => $config) {

            $rules["permissions.{$module}"] = ['nullable', 'array'];

            foreach (array_keys($config['actions']) as $action) {
                $rules["permissions.{$module}.{$action}"] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [

            'name.required' => 'Nama pengguna wajib diisi.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',

            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',

            'role.required' => 'Role pengguna wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',

            'status.required' => 'Status pengguna wajib dipilih.',
            'status.in' => 'Status pengguna tidak valid.',
        ];
    }
}


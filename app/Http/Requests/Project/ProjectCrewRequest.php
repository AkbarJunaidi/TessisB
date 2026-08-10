<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectCrewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dilindungi auth + role middleware global
    }

    public function rules(): array
    {
        return [
            'crew'                 => ['nullable', 'array'],
            'crew.*.user_id'       => ['required', 'integer', Rule::exists('users', 'id')],
            'crew.*.role_label'    => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'crew.*.user_id.required' => 'Setiap baris crew wajib memilih user.',
            'crew.*.user_id.exists'   => 'User yang dipilih tidak terdaftar di sistem.',
            'crew.*.role_label.required' => 'Peran (role) crew wajib diisi, contoh: Videographer.',
        ];
    }

    /**
     * Validasi tambahan: satu user tidak boleh didaftarkan dua kali
     * sebagai crew pada submit yang sama.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $crew = $this->input('crew', []);
            $userIds = array_column($crew, 'user_id');

            if (count($userIds) !== count(array_unique($userIds))) {
                $validator->errors()->add('crew', 'Satu user tidak boleh didaftarkan lebih dari sekali sebagai crew.');
            }
        });
    }
}

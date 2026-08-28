<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ProjectCrewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dilindungi auth + role middleware global
    }

    public function rules(): array
    {
        return [
            'crew'              => ['nullable', 'array'],
            'crew.*.name'       => ['required', 'string', 'max:80'],
            'crew.*.role_label' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'crew.*.name.required'       => 'Nama crew wajib diisi.',
            'crew.*.name.max'            => 'Nama crew maksimal 80 karakter.',
            'crew.*.role_label.required' => 'Peran (role) crew wajib diisi, contoh: Videographer.',
        ];
    }
}

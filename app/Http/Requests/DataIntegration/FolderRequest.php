<?php

namespace App\Http\Requests\DataIntegration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FolderRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('data_integration', 'create_folder') ?? false;
    }

    /**
     * Aturan validasi data Folder.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Mencegah karakter yang tidak diperbolehkan pada nama folder.
                'regex:/^[^\\\\\/\?%\*:|"<>]+$/',
            ],

            'parent_id' => [
                'nullable',
                'exists:folders,id',
            ],
        ];
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama folder wajib diisi.',
            'name.string'   => 'Nama folder harus berupa teks.',
            'name.max'      => 'Nama folder maksimal 255 karakter.',
            'name.regex'    => 'Nama folder tidak boleh mengandung karakter \\ / ? % * : | " < >.',

            'parent_id.exists' => 'Folder tujuan tidak ditemukan.',
        ];
    }

    /**
     * Nama atribut agar lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [
            'name'      => 'Nama Folder',
            'parent_id' => 'Folder Induk',
        ];
    }
}

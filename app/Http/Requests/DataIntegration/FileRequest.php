<?php

namespace App\Http\Requests\DataIntegration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FileRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Aturan validasi berdasarkan aksi yang dilakukan.
     */
    public function rules(): array
    {
        switch ($this->route()->getActionMethod()) {

            case 'store':
                return [
                    'file' => [
                        'required',
                        'file',
                        'max:10240', // 10 MB
                    ],

                    'folder_id' => [
                        'nullable',
                        'exists:folders,id',
                    ],
                ];

            case 'rename':
                return [
                    'file_name' => [
                        'required',
                        'string',
                        'max:255',
                        'regex:/^[a-zA-Z0-9_\-\s\.]+$/',
                    ],
                ];

            case 'move':
                return [
                    'target_folder_id' => [
                        'nullable',
                        'exists:folders,id',
                    ],
                ];

            default:
                return [];
        }
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [

            'file.required' => 'Silakan pilih file yang akan diunggah.',
            'file.file' => 'File yang dipilih tidak valid.',
            'file.max' => 'Ukuran file maksimal 10 MB.',

            'folder_id.exists' => 'Folder tujuan tidak ditemukan.',

            'file_name.required' => 'Nama file wajib diisi.',
            'file_name.max' => 'Nama file maksimal 255 karakter.',
            'file_name.regex' => 'Nama file mengandung karakter yang tidak diperbolehkan.',

            'target_folder_id.exists' => 'Folder tujuan ti          dak ditemukan.',
        ];
    }

    /**
     * Nama atribut.
     */
    public function attributes(): array
    {
        return [

            'file' => 'File',

            'folder_id' => 'Folder',

            'file_name' => 'Nama File',

            'target_folder_id' => 'Folder Tujuan',
        ];
    }
}



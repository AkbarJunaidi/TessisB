<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ContactRequest extends FormRequest
{
    /**
     * Otorisasi akses fitur ini ditangani lewat role gate di route
     * (role:super_admin,admin,employee - sama seperti Project). Di sini
     * cukup pastikan user sudah login.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Aturan validasi data Kontak.
     */
    public function rules(): array
    {
        return [

            'name' => ['required', 'string', 'max:255'],

            'company' => ['nullable', 'string', 'max:255'],

            'phone' => ['required', 'string', 'max:30'],

            'email' => ['nullable', 'email', 'max:255'],

            'address' => ['nullable', 'string', 'max:1000'],

        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'name.required'  => 'Nama client wajib diisi.',
            'phone.required' => 'No. HP/WA wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ];
    }

    /**
     * Nama atribut agar pesan validasi lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [
            'name'    => 'Nama Client',
            'company' => 'Nama Perusahaan',
            'phone'   => 'No. HP/WA',
            'email'   => 'Email',
            'address' => 'Alamat',
        ];
    }
}

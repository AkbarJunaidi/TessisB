<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

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
     * Checkbox toggle "Punya WhatsApp" tidak terkirim sama sekali kalau
     * tidak dicentang - normalisasi jadi boolean eksplisit di sini.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_whatsapp' => $this->boolean('has_whatsapp'),
            'is_client'    => $this->boolean('is_client'),
            'is_vendor'    => $this->boolean('is_vendor'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (!$this->boolean('is_client') && !$this->boolean('is_vendor')) {
                $v->errors()->add('is_client', 'Pilih minimal satu tag: Client atau Vendor.');
            }
        });
    }

    /**
     * Aturan validasi data Kontak.
     */
    public function rules(): array
    {
        return [

            'name' => ['required', 'string', 'max:255'],

            'company' => ['nullable', 'string', 'max:255'],

            'phone' => ['nullable', 'string', 'max:30'],

            'has_whatsapp' => ['boolean'],

            'is_client' => ['boolean'],

            'is_vendor' => ['boolean'],

            'email' => ['nullable', 'email', 'max:255'],

            'address' => ['nullable', 'string', 'max:1000'],

            'notes' => ['nullable', 'string', 'max:2000'],

        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kontak wajib diisi.',
            'email.email'   => 'Format email tidak valid.',
        ];
    }

    /**
     * Nama atribut agar pesan validasi lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [
            'name'    => 'Nama Kontak',
            'company' => 'Nama Perusahaan',
            'phone'   => 'No. HP/WA',
            'email'   => 'Email',
            'address' => 'Alamat',
            'notes'   => 'Catatan',
        ];
    }
}

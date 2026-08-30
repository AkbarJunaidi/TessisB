<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan mengirim request login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi form login.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            // Checkbox HTML tanpa atribut `value` eksplisit mengirim string
            // "on" saat dicentang (bukan true/1) - jadi field ini sengaja
            // TIDAK divalidasi ketat sebagai boolean, karena akan menolak
            // nilai bawaan checkbox itu sendiri. Nilainya cukup dinormalisasi
            // lewat $request->boolean('remember') di Controller, yang memang
            // dirancang menangani "on" dan variasi checkbox lainnya.
            'remember' => [
                'nullable',
            ],
        ];
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password tidak valid.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    /**
     * Nama atribut.
     */
    public function attributes(): array
    {
        return [
            'email' => 'Email',
            'password' => 'Password',
            'remember' => 'Remember Me',
        ];
    }
}


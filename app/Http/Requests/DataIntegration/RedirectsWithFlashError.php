<?php

namespace App\Http\Requests\DataIntegration;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validasi gagal -> kembali ke halaman sebelumnya dengan toast error global (layout),
 * karena modal di halaman Integrasi Data tidak menampilkan $errors.
 */
trait RedirectsWithFlashError
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(
            redirect()->back()->with('error', $validator->errors()->first())
        );
    }
}

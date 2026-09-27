<?php

namespace App\Http\Requests\Signature;

use Illuminate\Foundation\Http\FormRequest;

class SignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'file'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'canvas_data' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('canvas_data') && !$this->hasFile('file')) {
                $validator->errors()->add('file', 'Gambar tanda tangan (kanvas atau file) wajib diisi.');
            }
        });
    }
}

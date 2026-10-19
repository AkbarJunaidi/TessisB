<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Yang memegang dana (bos) adalah penyetuju; finance menerima dan membayar lewat izin pay.
        return $this->user()?->hasPermission('approval', 'decide') ?? false;
    }

    /**
     * Nominal dikirim berformat titik ribuan (mis. "1.500.000") - dibersihkan jadi angka murni.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => str_replace('.', '', (string) $this->input('amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'transferred_on' => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'note'           => ['nullable', 'string', 'max:255'],
            'proof'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'transferred_on.required' => 'Tanggal transfer wajib diisi.',
            'amount.required'         => 'Nominal transfer wajib diisi.',
            'amount.numeric'          => 'Nominal transfer harus berupa angka.',
            'amount.min'              => 'Nominal transfer minimal Rp 1.',
            'proof.mimes'             => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.max'               => 'Ukuran bukti maksimal 5MB.',
        ];
    }
}

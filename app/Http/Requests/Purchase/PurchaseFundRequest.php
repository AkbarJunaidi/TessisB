<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseFundRequest extends FormRequest
{
    // Transfer dana ke finance dicatat penyetuju (bos).
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approval', 'decide') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => str_replace('.', '', (string) $this->input('amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'fund_at' => ['required', 'date'],
            'amount'  => ['required', 'numeric', 'min:1', 'max:' . \App\Support\Limits::money()],
            'note'    => ['nullable', 'string', 'max:255'],
            'proof'   => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'fund_at.required' => 'Tanggal transfer wajib diisi.',
            'amount.required'  => 'Nominal transfer wajib diisi.',
            'amount.min'       => 'Nominal transfer minimal 1.',
            'proof.required'   => 'Bukti transfer wajib dilampirkan.',
            'proof.mimes'      => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.max'        => 'Ukuran bukti maksimal 5MB.',
        ];
    }
}

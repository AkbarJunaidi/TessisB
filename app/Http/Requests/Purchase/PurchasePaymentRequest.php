<?php

namespace App\Http\Requests\Purchase;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'pay') ?? false;
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
            'paid_at'        => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:1', 'max:' . \App\Support\Limits::money()],
            'payment_method' => ['required', Rule::in(Purchase::PAYMENT_METHODS)],
            'proof'          => ['nullable', 'required_if:payment_method,Transfer', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_at.required'        => 'Tanggal pembayaran wajib diisi.',
            'amount.required'         => 'Nominal pembayaran wajib diisi.',
            'amount.min'              => 'Nominal pembayaran minimal 1.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'proof.required_if'       => 'Bukti transfer wajib dilampirkan.',
            'proof.mimes'             => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.max'               => 'Ukuran bukti maksimal 5MB.',
        ];
    }
}

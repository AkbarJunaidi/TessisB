<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'pay') ?? false;
    }

    public function rules(): array
    {
        return [
            'paid_at'        => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'proof'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_at.required' => 'Tanggal pembayaran wajib diisi.',
            'proof.mimes'      => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.max'        => 'Ukuran bukti maksimal 5MB.',
        ];
    }
}

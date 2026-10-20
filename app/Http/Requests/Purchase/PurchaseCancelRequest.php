<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'edit') ?? false;
    }

    /** Alasan wajib untuk pembelian yang sudah disetujui; aturannya dicek di service. */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Alasan maksimal 500 karakter.',
        ];
    }
}

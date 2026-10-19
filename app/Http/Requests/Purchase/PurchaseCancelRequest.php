<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('refund_amount')) {
            $this->merge(['refund_amount' => str_replace('.', '', (string) $this->input('refund_amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'reason'        => ['nullable', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'refunded_on'   => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((float) $this->input('refund_amount') > 0 && !$this->filled('refunded_on')) {
                $validator->errors()->add('refunded_on', 'Tanggal uang kembali wajib diisi.');
            }
        });
    }
}

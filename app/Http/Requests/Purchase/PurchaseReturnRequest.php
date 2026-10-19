<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'receive') ?? false;
    }

    /** Nominal dikirim berformat titik ribuan - dibersihkan jadi angka murni. */
    protected function prepareForValidation(): void
    {
        if ($this->filled('refund_amount')) {
            $this->merge(['refund_amount' => str_replace('.', '', (string) $this->input('refund_amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'qty'           => ['required', 'array'],
            'qty.*'         => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'reason'        => ['required', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'refunded_on'   => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $purchase = $this->route('purchase');
            $any = false;

            foreach ($purchase->items as $item) {
                $qty = (int) $this->input("qty.{$item->id}");

                if ($qty > $item->returnable()) {
                    $validator->errors()->add("qty.{$item->id}", "Jumlah retur \"{$item->name}\" melebihi barang yang sudah diterima ({$item->returnable()}).");
                }

                if ($qty > 0) {
                    $any = true;
                }
            }

            if (!$any) {
                $validator->errors()->add('qty', 'Isi jumlah barang yang diretur, minimal satu barang.');
            }

            if ((float) $this->input('refund_amount') > 0 && !$this->filled('refunded_on')) {
                $validator->errors()->add('refunded_on', 'Tanggal uang kembali wajib diisi.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'reason.required'       => 'Alasan retur wajib diisi.',
            'refund_amount.numeric' => 'Nominal uang kembali harus berupa angka.',
        ];
    }
}

<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'receive') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('refund')) {
            $this->merge(['refund' => str_replace('.', '', (string) $this->input('refund'))]);
        }
    }

    public function rules(): array
    {
        return [
            'lines'       => ['required', 'array'],
            'lines.*.qty' => ['nullable', 'integer', 'min:0', 'max:' . config('limits.qty_max')],
            'note'        => ['required', 'string', 'max:500'],
            'refund'      => ['nullable', 'numeric', 'min:0', 'max:' . config('limits.money_max')],
            'refunded_on' => ['nullable', 'date', 'required_with:refund'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Isi jumlah barang yang diretur.',
            'note.required'  => 'Alasan retur dan catatan refund wajib diisi.',
            'note.max'       => 'Catatan maksimal 500 karakter.',
            'refunded_on.required_with' => 'Tanggal uang kembali wajib diisi bila ada nominal.',
        ];
    }
}

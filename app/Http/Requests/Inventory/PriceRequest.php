<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Isi atau ubah harga beli manual satu barang dari Daftar Harga Barang.
 */
class PriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->hasPermission('inventory', 'edit') && $user->hasPermission('purchase', 'view'));
    }

    public function rules(): array
    {
        return [
            'harga_manual'        => ['nullable', 'integer', 'min:0', 'max:' . config('limits.money_max')],
            'ambang_ganti_persen' => ['nullable', 'integer', 'between:10,200'],
        ];
    }

    public function messages(): array
    {
        return [
            'harga_manual.integer' => 'Harga harus berupa angka.',
            'harga_manual.min'     => 'Harga tidak boleh negatif.',
            'harga_manual.max'     => 'Harga terlalu besar.',
            'ambang_ganti_persen.integer' => 'Ambang ganti harus berupa angka.',
            'ambang_ganti_persen.between' => 'Ambang ganti harus antara 10 dan 200 persen.',
        ];
    }

    /** Input berformat "1.500.000" dibersihkan jadi angka; kosong atau 0 berarti harga dikosongkan. */
    protected function prepareForValidation(): void
    {
        $digits = ltrim(preg_replace('/\D/', '', explode(',', (string) $this->input('harga_manual'))[0]), '0');

        $this->merge(['harga_manual' => $digits === '' ? null : $digits]);
    }
}

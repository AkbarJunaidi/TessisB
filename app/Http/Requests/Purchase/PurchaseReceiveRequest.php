<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'receive') ?? false;
    }

    /**
     * serials[itemId] - serial number barang baru, diisi saat barang benar-benar tiba.
     */
    public function rules(): array
    {
        return [
            'serials'   => ['nullable', 'array'],
            'serials.*' => ['required', 'string', 'max:255', 'distinct', 'unique:inventories,serial_number'],
        ];
    }

    public function messages(): array
    {
        return [
            'serials.*.required' => 'Serial number wajib diisi.',
            'serials.*.distinct' => 'Serial number tidak boleh kembar antar item.',
            'serials.*.unique'   => 'Serial number ini sudah terdaftar di Inventory.',
        ];
    }
}

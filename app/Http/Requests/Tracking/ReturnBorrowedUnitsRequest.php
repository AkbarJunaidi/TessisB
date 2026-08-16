<?php

namespace App\Http\Requests\Tracking;

use Illuminate\Foundation\Http\FormRequest;

class ReturnBorrowedUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('borrowed_items', 'process_return') ?? false;
    }

    public function rules(): array
    {
        return [
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct', 'exists:inventory_units,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_ids.required' => 'Pilih minimal 1 unit barang yang ingin dikembalikan.',
            'unit_ids.min'      => 'Pilih minimal 1 unit barang yang ingin dikembalikan.',
            'unit_ids.*.exists' => 'Salah satu unit barang tidak ditemukan.',
        ];
    }
}

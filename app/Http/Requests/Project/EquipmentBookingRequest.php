<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory', 'booking') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_id' => ['required', Rule::exists('inventories', 'id')->whereNull('deleted_at')],
            'qty'          => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_id.required' => 'Pilih barang yang mau dibooking.',
            'qty.required'          => 'Jumlah wajib diisi.',
            'qty.min'                => 'Jumlah minimal 1.',
        ];
    }
}

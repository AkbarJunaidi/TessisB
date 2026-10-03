<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory', 'manage_repairs') ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal_selesai' => ['required', 'date', 'before_or_equal:today'],
            'diambil_oleh'    => ['nullable', 'string', 'max:150'],
            'biaya'           => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'catatan_hasil'   => ['nullable', 'string', 'max:1000'],
            'hasil'           => ['nullable', 'array'],
            'hasil.*'         => ['in:Tersedia,Rusak'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_selesai.required'        => 'Tanggal barang diambil wajib diisi.',
            'tanggal_selesai.before_or_equal' => 'Tanggal selesai tidak boleh di masa depan.',
            'biaya.numeric'                   => 'Biaya harus berupa angka.',
        ];
    }
}

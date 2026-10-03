<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class RepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory', 'manage_repairs') ?? false;
    }

    public function rules(): array
    {
        return [
            'unit_ids'         => ['required', 'array', 'min:1'],
            'unit_ids.*'       => ['integer', 'distinct'],
            'vendor_id'        => ['nullable', 'integer', 'exists:contacts,id'],
            'tempat_nama'      => ['required', 'string', 'max:150'],
            'tempat_alamat'    => ['nullable', 'string', 'max:500'],
            'tanggal_masuk'    => ['required', 'date', 'before_or_equal:today'],
            'estimasi_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'diantar_oleh'     => ['nullable', 'string', 'max:150'],
            'keluhan'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_ids.required'              => 'Pilih minimal satu unit yang diservis.',
            'tempat_nama.required'           => 'Nama tempat servis wajib diisi.',
            'tanggal_masuk.required'         => 'Tanggal barang diantar wajib diisi.',
            'tanggal_masuk.before_or_equal'  => 'Tanggal diantar tidak boleh di masa depan.',
            'estimasi_selesai.after_or_equal' => 'Estimasi selesai tidak boleh sebelum tanggal diantar.',
        ];
    }
}

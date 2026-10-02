<?php

namespace App\Http\Requests\Tracking;

use App\Services\Inventory\LocationService;
use Illuminate\Foundation\Http\FormRequest;

class ReturnBorrowedUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('borrowed_items', 'process_return') ?? false;
    }

    public function rules(): array
    {
        $lokasi = LocationService::INPUT_RULES;

        // Halaman Barang Pinjaman (return-by-ids) selalu mengirim lokasi; jalur lain boleh tanpa.
        if ($this->route()?->getActionMethod() !== 'returnByIds') {
            $lokasi['lokasi_id'] = ['nullable', 'integer'];
        }

        return [
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct', 'exists:inventory_units,id'],
        ] + $lokasi;
    }

    public function messages(): array
    {
        return [
            'unit_ids.required' => 'Pilih minimal 1 unit barang yang ingin dikembalikan.',
            'unit_ids.min'      => 'Pilih minimal 1 unit barang yang ingin dikembalikan.',
            'unit_ids.*.exists' => 'Salah satu unit barang tidak ditemukan.',
            'lokasi_id.required' => 'Lokasi saat ini belum terdeteksi. Tunggu atau pilih lokasi secara manual.',
        ];
    }
}

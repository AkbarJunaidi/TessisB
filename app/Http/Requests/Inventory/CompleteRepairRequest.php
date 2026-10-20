<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\Validator;
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
            'biaya_item'      => ['nullable', 'array'],
            'biaya_item.*'    => ['nullable', 'numeric', 'min:0', 'max:' . \App\Support\Limits::money()],
            'catatan_hasil'   => ['nullable', 'string', 'max:1000'],
            'hasil'           => ['nullable', 'array'],
            'hasil.*'         => ['in:Tersedia,Rusak'],
            'catatan'                => ['nullable', 'array'],
            'catatan.*'              => ['nullable', 'array'],
            'catatan.*.Tersedia'     => ['nullable', 'string', 'max:500'],
            'catatan.*.Rusak'        => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Barang yang punya unit gagal wajib diberi catatan alasan gagalnya. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $repair = $this->route('repair');

            if (!$repair) {
                return;
            }

            $failed = $repair->items()->get(['id', 'inventory_id'])
                ->filter(fn ($item) => $this->input("hasil.{$item->id}") === 'Rusak')
                ->pluck('inventory_id')
                ->unique();

            foreach ($failed as $inventoryId) {
                if (trim((string) $this->input("catatan.{$inventoryId}.Rusak")) === '') {
                    $validator->errors()->add("catatan.{$inventoryId}.Rusak", 'Catatan unit gagal wajib diisi.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'tanggal_selesai.required'        => 'Tanggal barang diambil wajib diisi.',
            'tanggal_selesai.before_or_equal' => 'Tanggal selesai tidak boleh di masa depan.',
            'biaya_item.*.numeric'            => 'Biaya tiap unit harus berupa angka.',
            'biaya_item.*.min'                => 'Biaya tiap unit tidak boleh negatif.',
            'catatan.*.Tersedia.max'          => 'Catatan unit berhasil maksimal 500 karakter.',
            'catatan.*.Rusak.max'             => 'Catatan unit gagal maksimal 500 karakter.',
        ];
    }
}

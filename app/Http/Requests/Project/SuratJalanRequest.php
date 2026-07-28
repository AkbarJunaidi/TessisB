<?php

namespace App\Http\Requests\Project;

use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class SuratJalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dilindungi auth + role middleware ('role:super_admin') di route
    }

    public function rules(): array
    {
        return [
            'kepada' => ['required', 'string', 'max:255'],
            'pic'    => ['required', 'string', 'max:255'],
            'tanggal_terbit' => ['required', 'date'],

            'tanggal_keberangkatan' => ['nullable', 'date'],
            'jam_berangkat'         => ['nullable', 'date_format:H:i'],

            'tanggal_gladi_bersih' => ['nullable', 'date'],
            'waktu_gladi_bersih'   => ['nullable', 'date_format:H:i'],

            'tanggal_acara'         => ['nullable', 'date'],
            'tanggal_acara_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_acara'],
            'waktu_acara'           => ['nullable', 'string', 'max:50'],

            'lokasi_acara' => ['required', 'string', 'max:255'],
            'catatan'      => ['nullable', 'string', 'max:1000'],

            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_id'      => ['required', 'integer', 'exists:inventories,id'],
            'items.*.kategori_item'     => ['nullable', 'string', 'max:100'],
            'items.*.qty'               => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'kepada.required'        => 'Nama penerima (Kepada) wajib diisi.',
            'pic.required'           => 'PIC wajib diisi.',
            'tanggal_terbit.required' => 'Tanggal terbit wajib diisi.',
            'lokasi_acara.required'  => 'Lokasi acara wajib diisi.',
            'tanggal_acara_selesai.after_or_equal' => 'Tanggal acara selesai tidak boleh sebelum tanggal acara mulai.',
            'items.required'   => 'Minimal harus memilih 1 barang.',
            'items.min'        => 'Minimal harus memilih 1 barang.',
            'items.*.inventory_id.required' => 'Barang wajib dipilih pada setiap baris.',
            'items.*.inventory_id.exists'    => 'Barang yang dipilih tidak ditemukan.',
            'items.*.qty.required' => 'Jumlah barang wajib diisi pada setiap baris.',
            'items.*.qty.min'      => 'Jumlah barang minimal 1 unit.',
        ];
    }

    /**
     * Validasi lapis kedua (server-side, tidak bisa dimanipulasi dari client):
     * - Total qty yang diminta per inventory (dijumlahkan lintas baris) tidak boleh
     *   melebihi qty_available saat ini.
     * - Tidak boleh ada inventory_id yang sama pada baris berbeda (harus digabung).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = collect($this->input('items', []));

            if ($items->isEmpty()) {
                return;
            }

            $duplicateIds = $items->pluck('inventory_id')->duplicates();
            if ($duplicateIds->isNotEmpty()) {
                $validator->errors()->add('items', 'Setiap barang hanya boleh muncul satu kali. Gabungkan jumlahnya pada satu baris.');
                return;
            }

            $requestedQtyByInventory = $items->groupBy('inventory_id')
                ->map(fn ($rows) => collect($rows)->sum('qty'));

            $inventories = Inventory::whereIn('id', $requestedQtyByInventory->keys())->get()->keyBy('id');

            foreach ($requestedQtyByInventory as $inventoryId => $requestedQty) {
                $inventory = $inventories->get($inventoryId);

                if (!$inventory) {
                    continue;
                }

                if ($requestedQty > $inventory->qty_available) {
                    $validator->errors()->add(
                        'items',
                        "Stok \"{$inventory->name}\" tidak mencukupi. Diminta {$requestedQty} unit, tersedia {$inventory->qty_available} unit."
                    );
                }
            }
        });
    }
}

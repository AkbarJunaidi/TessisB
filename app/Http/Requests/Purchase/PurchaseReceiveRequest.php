<?php

namespace App\Http\Requests\Purchase;

use App\Models\PurchaseItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'receive') ?? false;
    }

    /**
     * qty[itemId]     - jumlah diterima sekarang (kosong = semua sisa)
     * price[itemId]   - harga aktual satuan bila berbeda dari PO (hanya berlaku sebelum dibayar)
     * serials[itemId] - serial number barang baru yang baru pertama kali masuk
     */
    public function rules(): array
    {
        return [
            'qty'       => ['nullable', 'array'],
            'qty.*'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'price'     => ['nullable', 'array'],
            'price.*'   => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'serials'   => ['nullable', 'array'],
            'serials.*' => ['nullable', 'string', 'max:255', 'distinct', 'unique:inventories,serial_number'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $purchase = $this->route('purchase');
            $anyReceived = false;

            foreach ($purchase->items as $item) {
                $raw = $this->input("qty.{$item->id}");
                $now = ($raw === null || $raw === '') ? $item->remaining() : (int) $raw;

                if ($now > $item->remaining()) {
                    $validator->errors()->add("qty.{$item->id}", "Jumlah \"{$item->name}\" melebihi sisa pesanan ({$item->remaining()}).");
                }

                if ($now > 0) {
                    $anyReceived = true;
                }

                $needsSerial = $item->stock_mode === PurchaseItem::STOCK_NEW && !$item->inventory_id;
                if ($now > 0 && $needsSerial && trim((string) $this->input("serials.{$item->id}")) === '') {
                    $validator->errors()->add("serials.{$item->id}", "Serial number untuk \"{$item->name}\" wajib diisi.");
                }
            }

            if (!$anyReceived) {
                $validator->errors()->add('qty', 'Isi jumlah barang yang diterima, minimal satu barang.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'qty.*.integer'      => 'Jumlah diterima harus berupa angka bulat.',
            'qty.*.min'          => 'Jumlah diterima tidak boleh negatif.',
            'price.*.numeric'    => 'Harga harus berupa angka.',
            'serials.*.distinct' => 'Serial number tidak boleh kembar antar item.',
            'serials.*.unique'   => 'Serial number ini sudah terdaftar di Inventory.',
        ];
    }
}

<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'receive') ?? false;
    }

    /** Harga tampil bertitik ribuan di form; hilangkan titiknya sebelum divalidasi. */
    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines');

        if (is_array($lines)) {
            $this->merge(['lines' => array_map(function ($line) {
                if (is_array($line) && isset($line['price'])) {
                    $line['price'] = str_replace('.', '', (string) $line['price']);
                }

                return $line;
            }, $lines)]);
        }
    }

    /**
     * lines[itemId] - qty diterima, serial (barang baru, penerimaan pertama), dan harga aktual per unit.
     * Serial dicek di service karena hanya relevan untuk item yang diterima dan belum terdaftar.
     */
    public function rules(): array
    {
        return [
            'lines'          => ['required', 'array'],
            'lines.*.qty'    => ['nullable', 'integer', 'min:0', 'max:' . \App\Support\Limits::qty()],
            'lines.*.serial' => ['nullable', 'string', \App\Support\TextLimits::max(\App\Support\TextLimits::SERIAL, 'Serial number')],
            'lines.*.price'  => ['nullable', 'numeric', 'min:0', 'max:' . \App\Support\Limits::money()],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required'      => 'Isi jumlah barang yang diterima.',
            'lines.*.qty.integer' => 'Jumlah diterima harus berupa angka bulat.',
            'lines.*.qty.min'     => 'Jumlah diterima tidak boleh negatif.',
            'lines.*.price.numeric' => 'Harga aktual harus berupa angka.',
        ];
    }
}

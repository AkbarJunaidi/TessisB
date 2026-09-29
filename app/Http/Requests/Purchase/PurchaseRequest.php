<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseRequest extends FormRequest
{
    private const MAX_TOTAL = 9999999999999.99;

    public function authorize(): bool
    {
        $action = $this->route()?->getActionMethod() === 'update' ? 'edit' : 'create';

        return $this->user()?->hasPermission('purchase', $action) ?? false;
    }

    /**
     * Harga dikirim berformat titik ribuan (mis. "1.500.000") - dibersihkan jadi angka murni.
     */
    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_array($items)) {
            $items = array_map(function ($row) {
                if (is_array($row)) {
                    $row['unit_price'] = str_replace('.', '', (string) ($row['unit_price'] ?? ''));
                    $row['stock_mode'] = $row['stock_mode'] ?? 'none';
                }

                return $row;
            }, $items);

            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'vendor_id'         => ['required', Rule::exists('contacts', 'id')->where('is_vendor', true)],
            'project_id'        => ['nullable', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'purchase_date'     => ['required', 'date'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'attachment'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],

            'items'                => ['required', 'array', 'min:1', 'max:50'],
            'items.*.name'         => ['required', 'string', 'max:150'],
            'items.*.qty'          => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0', 'max:999999999'],
            'items.*.stock_mode'   => ['required', Rule::in(['none', 'existing', 'new'])],
            'items.*.inventory_id' => [
                'nullable',
                'required_if:items.*.stock_mode,existing',
                Rule::exists('inventories', 'id')->whereNull('deleted_at'),
            ],
            'items.*.brand'        => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $total = collect($this->input('items'))
                ->sum(fn ($row) => (int) $row['qty'] * (float) $row['unit_price']);

            if ($total > self::MAX_TOTAL) {
                $v->errors()->add('items', 'Total pembelian melebihi batas yang bisa disimpan.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'vendor_id.required'             => 'Vendor wajib dipilih.',
            'vendor_id.exists'               => 'Vendor tidak valid. Pastikan kontaknya bertag Vendor.',
            'purchase_date.required'         => 'Tanggal pembelian wajib diisi.',
            'items.required'                 => 'Minimal 1 item pembelian.',
            'items.min'                      => 'Minimal 1 item pembelian.',
            'items.*.name.required'          => 'Nama item wajib diisi.',
            'items.*.qty.required'           => 'Jumlah item wajib diisi.',
            'items.*.qty.min'                => 'Jumlah item minimal 1.',
            'items.*.unit_price.required'    => 'Harga satuan wajib diisi.',
            'items.*.unit_price.numeric'     => 'Harga satuan harus berupa angka.',
            'items.*.inventory_id.required_if' => 'Pilih barang Inventory untuk item yang menambah stok.',
            'attachment.mimes'               => 'Lampiran harus berupa JPG, PNG, WEBP, atau PDF.',
            'attachment.max'                 => 'Ukuran lampiran maksimal 5MB.',
        ];
    }
}

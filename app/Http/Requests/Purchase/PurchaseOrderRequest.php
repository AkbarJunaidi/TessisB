<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'edit') ?? false;
    }

    public function rules(): array
    {
        $online = $this->route('purchase')?->isOnline() ?? false;

        return [
            'ordered_at'       => ['nullable', 'date'],
            'expected_arrival' => ['nullable', 'date'],
            'order_notes'      => ['nullable', 'string', 'max:500'],
            'shop_name'        => [$online ? 'required' : 'nullable', 'string', 'max:150'],
            'order_number'     => [$online ? 'required' : 'nullable', 'string', 'max:100'],
            'order_url'        => ['nullable', 'url', 'max:500'],
            'courier'          => ['nullable', 'string', 'max:100'],
            'tracking_number'  => ['nullable', 'string', 'max:100'],
            'proof'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_name.required'    => 'Nama toko online wajib diisi.',
            'order_number.required' => 'Nomor pesanan toko online wajib diisi.',
            'order_url.url'         => 'Tautan pesanan harus berupa alamat web yang valid.',
            'proof.mimes'           => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'proof.max'             => 'Ukuran bukti maksimal 5MB.',
        ];
    }
}

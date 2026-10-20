<?php

namespace App\Http\Requests\Purchase;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    // Menandai Dipesan memakai izin edit (pembuat dan Admin).
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase', 'edit') ?? false;
    }

    public function rules(): array
    {
        $purchase = $this->route('purchase');
        $file = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

        return [
            'ordered_at'      => ['nullable', 'date', 'before_or_equal:today'],
            'expected_at'     => ['nullable', 'date'],
            'store_name'      => ['nullable', 'string', 'max:100'],
            'order_number'    => [Rule::requiredIf($purchase instanceof Purchase && $purchase->isOnline()), 'nullable', 'string', 'max:100'],
            'order_url'       => ['nullable', 'url', 'max:500'],
            'courier'         => ['nullable', 'string', 'max:50'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'note'            => ['nullable', 'string', 'max:255'],
            'order_proof'     => $file,
            'receipt_file'    => $file,
        ];
    }

    public function messages(): array
    {
        return [
            'order_number.required' => 'Nomor pesanan wajib diisi untuk belanja toko online.',
            'ordered_at.before_or_equal' => 'Tanggal pesan tidak boleh di masa depan.',
            'order_url.url'         => 'Tautan pesanan harus berupa alamat web yang valid.',
            'order_proof.mimes'     => 'Bukti pesanan harus berupa JPG, PNG, WEBP, atau PDF.',
            'receipt_file.mimes'    => 'Resi harus berupa JPG, PNG, WEBP, atau PDF.',
            'order_proof.max'       => 'Ukuran bukti pesanan maksimal 5MB.',
            'receipt_file.max'      => 'Ukuran resi maksimal 5MB.',
        ];
    }
}

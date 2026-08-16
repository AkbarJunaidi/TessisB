<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ReturnBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('borrowed_items', 'process_return') ?? false;
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $sisa = $item ? ($item->qty_dipakai - $item->qty_dikembalikan) : 0;

        return [
            'qty' => ['required', 'integer', 'min:1', "max:{$sisa}"],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.required' => 'Jumlah yang dikembalikan wajib diisi.',
            'qty.min'      => 'Jumlah yang dikembalikan minimal 1 unit.',
            'qty.max'      => 'Jumlah yang dikembalikan tidak boleh melebihi sisa barang yang masih dipakai.',
        ];
    }
}

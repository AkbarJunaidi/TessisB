<?php

namespace App\Http\Requests\Project;

use App\Services\Inventory\LocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        // Lokasi wajib selama ada lokasi penyimpanan aktif (dikirim modal pilih lokasi).
        $lokasi = ['lokasi_id' => [Rule::requiredIf(fn () => app(LocationService::class)->getStorageLocations()->isNotEmpty()), 'nullable', 'integer']]
            + array_diff_key(LocationService::INPUT_RULES, ['lokasi_id' => true]);

        return [
            'qty' => ['required', 'integer', 'min:1', "max:{$sisa}"],
        ] + $lokasi;
    }

    public function messages(): array
    {
        return [
            'qty.required' => 'Jumlah yang dikembalikan wajib diisi.',
            'qty.min'      => 'Jumlah yang dikembalikan minimal 1 unit.',
            'lokasi_id.required' => 'Lokasi saat ini belum terdeteksi. Tunggu atau pilih lokasi secara manual.',
            'qty.max'      => 'Jumlah yang dikembalikan tidak boleh melebihi sisa barang yang masih dipakai.',
        ];
    }
}

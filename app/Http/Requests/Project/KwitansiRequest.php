<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KwitansiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('finance', 'create_kwitansi') ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal'             => ['required', 'date', 'before_or_equal:today'],
            'jumlah'              => ['required', 'numeric', 'min:1', 'max:' . \App\Support\Limits::money()],
            'metode_pembayaran'   => ['nullable', 'string', 'max:50'],
            'keterangan'          => ['nullable', 'string', 'max:255'],
            'kategori_pembayaran' => ['nullable', 'in:booking_fee,dp,pelunasan'],
            'link_item_id'        => ['nullable', 'integer'],
            'confirmed'           => ['nullable', 'boolean'],
            // Wajib milik user yang sedang login - cegah orang memilih
            // tanda tangan user lain lewat manipulasi request.
            'signature_id' => ['nullable', Rule::exists('signatures', 'id')->where('user_id', Auth::id())],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.before_or_equal' => 'Tanggal kwitansi tidak boleh melewati hari ini.',
        ];
    }
}

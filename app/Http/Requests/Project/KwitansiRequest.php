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
            'tanggal'             => ['required', 'date'],
            'jumlah'              => ['required', 'numeric', 'min:1'],
            'metode_pembayaran'   => ['nullable', 'string', 'max:50'],
            'keterangan'          => ['nullable', 'string', 'max:255'],
            'kategori_pembayaran' => ['nullable', 'in:booking_fee,dp,pelunasan'],
            // Wajib milik user yang sedang login - cegah orang memilih
            // tanda tangan user lain lewat manipulasi request.
            'signature_id' => ['nullable', Rule::exists('signatures', 'id')->where('user_id', Auth::id())],
        ];
    }
}

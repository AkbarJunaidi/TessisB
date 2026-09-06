<?php

namespace App\Http\Requests\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ActivityLogDeleteRangeRequest extends FormRequest
{
    /**
     * Otorisasi fitur ini ditangani berlapis: role gate "super_admin" pada
     * route, lalu dicek ulang secara eksplisit di ActivityLogController
     * (mengikuti pola otorisasi ganda yang sama dengan
     * TrashController::forceDelete()). Di sini cukup pastikan user login.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Normalisasi input sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => $this->filled('date_from') ? trim($this->date_from) : null,
            'date_to'   => $this->filled('date_to') ? trim($this->date_to) : null,
        ]);
    }

    /**
     * Aturan validasi rentang tanggal Activity Log yang akan dihapus.
     */
    public function rules(): array
    {
        return [

            'date_from' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'date_from.required' => 'Tanggal awal wajib diisi.',
            'date_from.date_format' => 'Format tanggal awal harus YYYY-MM-DD.',
            'date_to.required' => 'Tanggal akhir wajib diisi.',
            'date_to.date_format' => 'Format tanggal akhir harus YYYY-MM-DD.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ];
    }

    /**
     * Nama atribut agar pesan validasi lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [
            'date_from' => 'Tanggal Awal',
            'date_to' => 'Tanggal Akhir',
        ];
    }
}

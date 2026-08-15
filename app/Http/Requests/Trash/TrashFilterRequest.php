<?php

namespace App\Http\Requests\Trash;

use App\Services\Trash\TrashService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TrashFilterRequest extends FormRequest
{
    /**
     * Otorisasi akses halaman Trash ditangani oleh RoleMiddleware pada route
     * (super_admin & admin). Di sini cukup pastikan user sudah login.
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
            'search' => $this->filled('search') ? trim($this->search) : null,

            'type' => $this->filled('type') ? trim($this->type) : null,

            'deleted_by' => $this->filled('deleted_by') ? $this->deleted_by : null,

            'date_from' => $this->filled('date_from') ? $this->date_from : null,

            'date_to' => $this->filled('date_to') ? $this->date_to : null,

            'per_page' => $this->filled('per_page') ? (int) $this->per_page : 10,
        ]);
    }

    /**
     * Aturan validasi filter Trash.
     */
    public function rules(): array
    {
        $allowedTypes = array_keys(app(TrashService::class)->getTypeOptions());

        return [

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'type' => [
                'nullable',
                'string',
                Rule::in($allowedTypes),
            ],

            'deleted_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'date_from' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([10, 25, 50, 100]),
            ],

        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Kata kunci pencarian maksimal 100 karakter.',
            'type.in' => 'Tipe data yang dipilih tidak valid.',
            'deleted_by.exists' => 'User tidak ditemukan.',
            'date_from.date_format' => 'Format tanggal awal harus YYYY-MM-DD.',
            'date_to.date_format' => 'Format tanggal akhir harus YYYY-MM-DD.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
            'per_page.in' => 'Jumlah data per halaman tidak valid.',
        ];
    }

    /**
     * Nama atribut agar pesan validasi lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [
            'search' => 'Pencarian',
            'type' => 'Tipe Data',
            'deleted_by' => 'Dihapus Oleh',
            'date_from' => 'Tanggal Dari',
            'date_to' => 'Tanggal Sampai',
            'per_page' => 'Jumlah Data',
        ];
    }
}

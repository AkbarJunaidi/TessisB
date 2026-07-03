<?php

namespace App\Http\Requests\ActivityLog;

use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ActivityLogFilterRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan mengakses filter Activity Log.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Mengubah nilai kosong menjadi null
     * sebelum proses validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([

            'module' => $this->filled('module')
                ? $this->module
                : null,

            'user_id' => $this->filled('user_id')
                ? $this->user_id
                : null,

            'action' => $this->filled('action')
                ? $this->action
                : null,

            'search' => $this->filled('search')
                ? trim($this->search)
                : null,

            'date_from' => $this->filled('date_from')
                ? $this->date_from
                : null,

            'date_to' => $this->filled('date_to')
                ? $this->date_to
                : null,

            'per_page' => $this->filled('per_page')
                ? $this->per_page
                : 10,

        ]);
    }

    /**
     * Aturan validasi filter Activity Log.
     */
    public function rules(): array
    {
        $activityLogService = app(ActivityLogService::class);

        $modules = $activityLogService->getModules();

        $actions = collect(
            $activityLogService->getActions()
        )
            ->flatten()
            ->values()
            ->all();
           

        return [

            'module' => [
                'nullable',
                Rule::in($modules),
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'action' => [
                'nullable',
                Rule::in($actions),
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
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
                Rule::in([
                    10,
                    25,
                    50,
                    100,
                ]),
            ],

        ];
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [

            'module.in' => 'Modul yang dipilih tidak valid.',

            'action.in' => 'Jenis aktivitas tidak valid.',

            'user_id.exists' => 'User tidak ditemukan.',

            'search.max' => 'Kata kunci pencarian maksimal 100 karakter.',

            'date_from.date_format' => 'Format tanggal awal harus YYYY-MM-DD.',

            'date_to.date_format' => 'Format tanggal akhir harus YYYY-MM-DD.',

            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',

            'per_page.in' => 'Jumlah data per halaman tidak valid.',

        ];
    }

    /**
     * Nama atribut agar lebih mudah dibaca.
     */
    public function attributes(): array
    {
        return [

            'module' => 'Modul',

            'user_id' => 'User',

            'action' => 'Aktivitas',

            'search' => 'Pencarian',

            'date_from' => 'Tanggal Awal',

            'date_to' => 'Tanggal Akhir',

            'per_page' => 'Jumlah Data',

        ];
    }
}

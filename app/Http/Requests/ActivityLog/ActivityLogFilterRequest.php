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
     * Mengubah nilai kosong atau opsi 'all' menjadi null
     * sebelum proses validasi.
     */
    protected function prepareForValidation(): void
    {
        // 1. Normalisasi Input Module
        $module = $this->filled('module') ? trim($this->module) : null;
        if ($module !== null && in_array(strtolower($module), ['all', 'all_module', 'all modules', 'semua', 'semua modul'])) {
            $module = null;
        }

        // 2. Normalisasi Input Action
        $action = $this->filled('action') ? trim($this->action) : null;
        if ($action !== null && in_array(strtolower($action), ['all', 'all_action', 'all actions', 'semua', 'semua aktivitas'])) {
            $action = null;
        }

        $this->merge([
            'module' => $module,

            'user_id' => $this->filled('user_id')
                ? $this->user_id
                : null,

            'action' => $action,

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
                ? (int) $this->per_page
                : 10,
        ]);
    }

    /**
     * Aturan validasi filter Activity Log.
     */
    public function rules(): array
    {
        $activityLogService = app(ActivityLogService::class);

        // 1. Ambil & susun daftar modul yang diizinkan
        $modulesDict = $activityLogService->getModules();
        $allowedModules = array_unique(array_merge(
            array_keys($modulesDict),
            array_values($modulesDict),
            [
                'Inventory',
                'Authentication',
                'Tracking Progress',
                'Integrasi Data',
                'User Management',
                'User',
                'all',
                'all_module',
                'all modules',
                'semua'
            ]
        ));

        // 2. Ambil daftar action, ratakan (flatten), dan tambahkan kata kunci aksi umum
        $actionsFromService = collect($activityLogService->getActions())
            ->flatten()
            ->filter()
            ->toArray();

        $allowedActions = array_unique(array_merge(
            $actionsFromService,
            [
                'created', 'updated', 'deleted', 'login', 'logout',
                'Created', 'Updated', 'Deleted', 'Login', 'Logout',
                'Create', 'Update', 'Delete',
                'all', 'all_action', 'all actions', 'semua'
            ]
        ));

        return [

            'module' => [
                'nullable',
                'string',
                Rule::in($allowedModules),
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'action' => [
                'nullable',
                'string',
                Rule::in($allowedActions),
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

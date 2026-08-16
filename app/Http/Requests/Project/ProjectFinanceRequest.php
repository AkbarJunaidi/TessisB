<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ProjectFinanceRequest extends FormRequest
{
    /**
     * Nominal dikirim dari input teks berformat titik ribuan (mis. "9.000.000"
     * ala Indonesia) - dibersihkan dulu jadi angka murni ("9000000") di sini,
     * sebelum divalidasi sebagai numeric dan sebelum disimpan ke database.
     */
    protected function prepareForValidation(): void
    {
        $clean = fn ($rows) => is_array($rows)
            ? array_map(function ($row) {
                if (isset($row['amount'])) {
                    $row['amount'] = str_replace('.', '', (string) $row['amount']);
                }
                return $row;
            }, $rows)
            : $rows;

        $this->merge([
            'incomes'  => $clean($this->input('incomes')),
            'expenses' => $clean($this->input('expenses')),
        ]);
    }

    /**
     * Default-nya hanya Super Admin yang bisa mengisi/mengubah data keuangan
     * (lihat config/permissions.php -> role_defaults.admin.finance.manage = false).
     * Bisa diaktifkan untuk role lain (mis. Admin) lewat Permission Override
     * pada halaman Edit User, tanpa perlu mengubah kode ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('finance', 'manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'incomes'                 => ['nullable', 'array'],
            'incomes.*.amount'        => ['required_with:incomes', 'numeric', 'min:0', 'max:999999999999.99'],
            'incomes.*.description'   => ['nullable', 'string', 'max:255'],

            'expenses'                => ['nullable', 'array'],
            'expenses.*.amount'       => ['required_with:expenses', 'numeric', 'min:0', 'max:999999999999.99'],
            'expenses.*.description'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'incomes.*.amount.required_with' => 'Nominal pendapatan wajib diisi.',
            'incomes.*.amount.numeric'       => 'Nominal pendapatan harus berupa angka.',
            'expenses.*.amount.required_with' => 'Nominal pengeluaran wajib diisi.',
            'expenses.*.amount.numeric'       => 'Nominal pengeluaran harus berupa angka.',
        ];
    }
}

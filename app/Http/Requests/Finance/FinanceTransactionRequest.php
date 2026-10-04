<?php

namespace App\Http\Requests\Finance;

use App\Models\Contact;
use App\Support\FinanceCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceTransactionRequest extends FormRequest
{
    /** Sama seperti Data Keuangan project: hanya yang punya izin finance.manage. */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('finance', 'manage') ?? false;
    }

    /** Nominal dikirim berformat titik ribuan ("9.000.000"), dibersihkan jadi angka murni. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => str_replace('.', '', (string) $this->input('amount')),
        ]);
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type'           => ['required', 'in:income,expense'],
            'amount'         => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'tanggal'        => ['required', 'date', 'before_or_equal:today'],
            'category'       => ['required', 'string', Rule::in(FinanceCategory::manual(in_array($type, ['income', 'expense'], true) ? $type : null))],
            'project_id'     => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'contact_id'     => ['nullable', 'integer', Rule::exists(Contact::class, 'id')],
            'recipient'      => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'description'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $projectCategories = [FinanceCategory::PROJECT_INCOME, FinanceCategory::PROJECT_EXPENSE];

            if (in_array($this->input('category'), $projectCategories, true) && !$this->filled('project_id')) {
                $validator->errors()->add('project_id', 'Project wajib dipilih untuk kategori ini.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required'        => 'Tipe transaksi wajib dipilih.',
            'amount.required'      => 'Nominal wajib diisi.',
            'amount.numeric'       => 'Nominal harus berupa angka.',
            'amount.min'           => 'Nominal harus lebih dari 0.',
            'tanggal.required'     => 'Tanggal wajib diisi.',
            'tanggal.date'         => 'Tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'category.required'    => 'Kategori wajib dipilih.',
            'category.in'          => 'Kategori tidak valid untuk tipe transaksi ini.',
            'project_id.exists'    => 'Project tidak ditemukan.',
            'contact_id.exists'    => 'Kontak tidak ditemukan.',
        ];
    }
}

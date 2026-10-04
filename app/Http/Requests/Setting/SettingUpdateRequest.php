<?php

namespace App\Http\Requests\Setting;

use App\Models\AppSetting;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\ProjectFinanceItem;
use App\Support\FinanceCategory;
use App\Support\InventoryStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $categories = collect($this->input('project_categories', []))
            ->map(fn ($c) => trim((string) $c))
            ->filter(fn ($c) => $c !== '')
            ->values()
            ->all();

        // Baris status kustom yang namanya kosong dibuang.
        $statuses = collect($this->input('inventory_statuses', []))
            ->map(fn ($row) => ['name' => trim((string) ($row['name'] ?? '')), 'color' => (string) ($row['color'] ?? '')])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values()
            ->all();

        // Baris kategori keuangan kustom yang namanya kosong dibuang.
        $financeCategories = collect($this->input('finance_categories', []))
            ->map(fn ($row) => ['name' => trim((string) ($row['name'] ?? '')), 'type' => (string) ($row['type'] ?? '')])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values()
            ->all();

        // Ekstensi dipisah koma/spasi/baris baru; titik dan karakter selain huruf-angka dibuang.
        $extensions = collect(preg_split('/[\s,;]+/', strtolower((string) $this->input('upload_allowed_extensions')), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($e) => preg_replace('/[^a-z0-9]/', '', $e))
            ->filter()
            ->unique()
            ->values();

        $this->merge([
            'project_categories'        => $categories,
            'inventory_statuses'        => $statuses,
            'finance_categories'        => $financeCategories,
            'upload_allowed_extensions' => $extensions->implode(','),
        ]);
    }

    public function rules(): array
    {
        $image = ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'];

        return [
            'company_name'           => ['required', 'string', 'max:100'],
            'company_tagline'        => ['nullable', 'string', 'max:100'],
            'company_address'        => ['required', 'string', 'max:255'],
            'company_phone'          => ['nullable', 'string', 'max:50'],
            'company_whatsapp'       => ['nullable', 'string', 'max:50'],
            'company_footer_tagline' => ['nullable', 'string', 'max:150'],
            'company_footer_contact' => ['nullable', 'string', 'max:150'],
            'company_website'        => ['nullable', 'string', 'max:100'],
            'company_instagram'      => ['nullable', 'string', 'max:100'],
            'company_tiktok'         => ['nullable', 'string', 'max:100'],

            'servis_segera_hari'      => ['required', 'integer', 'between:1,90'],
            'repair_warn_percent'     => ['required', 'integer', 'between:10,200'],
            'location_default_radius' => ['required', 'integer', 'between:10,5000'],
            'upload_max_mb'           => ['required', 'integer', 'between:1,50'],
            'finance_lock_date'       => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'upload_allowed_extensions' => [
                'required', 'string', 'max:300',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $blocked = array_intersect(explode(',', (string) $value), config('app_settings.blocked_extensions'));

                    if ($blocked !== []) {
                        $fail('Ekstensi berikut tidak boleh diizinkan: ' . implode(', ', $blocked) . '.');
                    }
                },
            ],

            'project_categories'   => ['required', 'array', 'min:1', 'max:50'],
            'project_categories.*' => ['string', 'max:100', 'distinct:ignore_case'],

            'inventory_statuses'        => ['nullable', 'array', 'max:20'],
            'inventory_statuses.*.name' => [
                'required', 'string', 'max:30', 'regex:/^[\p{L}\p{N}\s\-\/]+$/u', 'distinct:ignore_case',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $taken = array_merge(array_map('mb_strtolower', array_keys(InventoryStatus::SYSTEM)), InventoryStatus::RESERVED);

                    if (in_array(mb_strtolower((string) $value), $taken, true)) {
                        $fail('Nama status sudah dipakai sistem.');
                    }
                },
            ],
            'inventory_statuses.*.color' => ['required', 'in:' . implode(',', array_keys(InventoryStatus::COLORS))],

            'finance_categories'        => ['nullable', 'array', 'max:30'],
            'finance_categories.*.name' => [
                'required', 'string', 'max:50', 'regex:/^[\p{L}\p{N}\s\-\/]+$/u', 'distinct:ignore_case',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (in_array(mb_strtolower((string) $value), array_map('mb_strtolower', array_keys(FinanceCategory::SYSTEM)), true)) {
                        $fail('Nama kategori sudah dipakai sistem.');
                    }
                },
            ],
            'finance_categories.*.type' => ['required', 'in:income,expense'],

            'logo_pdf'  => $image,
            'kop_atas'  => $image,
            'kop_bawah' => $image,

            'reset_images'   => ['nullable', 'array'],
            'reset_images.*' => ['in:logo_pdf,kop_atas,kop_bawah'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Margin halaman PDF Inventory dihitung untuk kop berukuran sekitar 21 : 5 (lebar : tinggi).
        $validator->after(function (Validator $validator) {
            // Status kustom yang masih dipakai unit/barang tidak boleh dihapus.
            $kept = array_map('mb_strtolower', array_column($this->input('inventory_statuses', []), 'name'));

            foreach (AppSetting::inventoryStatuses() as $old) {
                if (in_array(mb_strtolower($old['name']), $kept, true)) {
                    continue;
                }

                $used = InventoryUnit::where('status', $old['name'])->count() + Inventory::where('status', $old['name'])->count();

                if ($used > 0) {
                    $validator->errors()->add('inventory_statuses', "Status \"{$old['name']}\" masih dipakai {$used} data barang/unit dan tidak bisa dihapus.");
                }
            }

            // Kategori keuangan kustom yang masih dipakai transaksi tidak boleh dihapus.
            $keptCategories = array_map('mb_strtolower', array_column($this->input('finance_categories', []), 'name'));

            foreach (AppSetting::financeCategories() as $old) {
                if (in_array(mb_strtolower($old['name']), $keptCategories, true)) {
                    continue;
                }

                $used = ProjectFinanceItem::where('category', $old['name'])->count();

                if ($used > 0) {
                    $validator->errors()->add('finance_categories', "Kategori \"{$old['name']}\" masih dipakai {$used} transaksi dan tidak bisa dihapus.");
                }
            }

            foreach (['kop_atas', 'kop_bawah'] as $key) {
                $file = $this->file($key);

                if (!$file || $validator->errors()->has($key)) {
                    continue;
                }

                [$w, $h] = @getimagesize($file->getRealPath()) ?: [0, 0];
                $ratio = $h > 0 ? $w / $h : 0;

                if ($w < 1240 || $ratio < 3.8 || $ratio > 4.6) {
                    $validator->errors()->add($key, 'Ukuran kop harus berlebar minimal 1240 px dengan perbandingan lebar : tinggi sekitar 21 : 5 (contoh 2480 x 594 px).');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_name.required'    => 'Nama perusahaan wajib diisi.',
            'company_address.required' => 'Alamat wajib diisi.',

            'servis_segera_hari.between'      => 'Batas servis segera harus antara 1 dan 90 hari.',
            'repair_warn_percent.between'     => 'Ambang biaya servis harus antara 10 dan 200 persen.',
            'location_default_radius.between' => 'Radius default harus antara 10 dan 5000 meter.',
            'upload_max_mb.between'           => 'Batas ukuran upload harus antara 1 dan 50 MB.',
            'finance_lock_date.date_format'   => 'Tanggal tutup buku tidak valid.',
            'finance_lock_date.before_or_equal' => 'Tanggal tutup buku tidak boleh melewati hari ini.',
            'upload_allowed_extensions.required' => 'Isi minimal satu ekstensi file yang diizinkan.',

            'project_categories.required'     => 'Minimal satu kategori project.',
            'project_categories.min'          => 'Minimal satu kategori project.',
            'project_categories.*.distinct'   => 'Ada kategori project yang sama.',
            'project_categories.*.max'        => 'Nama kategori maksimal 100 karakter.',

            'inventory_statuses.*.name.required' => 'Nama status wajib diisi.',
            'inventory_statuses.*.name.max'      => 'Nama status maksimal 30 karakter.',
            'inventory_statuses.*.name.regex'    => 'Nama status hanya boleh huruf, angka, spasi, tanda hubung, dan garis miring.',
            'inventory_statuses.*.name.distinct' => 'Ada nama status yang sama.',
            'inventory_statuses.*.color.in'      => 'Warna status tidak valid.',

            'finance_categories.*.name.required' => 'Nama kategori wajib diisi.',
            'finance_categories.*.name.max'      => 'Nama kategori maksimal 50 karakter.',
            'finance_categories.*.name.regex'    => 'Nama kategori hanya boleh huruf, angka, spasi, tanda hubung, dan garis miring.',
            'finance_categories.*.name.distinct' => 'Ada nama kategori yang sama.',
            'finance_categories.*.type.in'       => 'Tipe kategori tidak valid.',

            'logo_pdf.image'  => 'Logo harus berupa gambar PNG atau JPG.',
            'logo_pdf.mimes'  => 'Logo harus berformat PNG atau JPG.',
            'logo_pdf.max'    => 'Ukuran logo maksimal 2 MB.',
            'kop_atas.image'  => 'Kop atas harus berupa gambar PNG atau JPG.',
            'kop_atas.mimes'  => 'Kop atas harus berformat PNG atau JPG.',
            'kop_atas.max'    => 'Ukuran kop atas maksimal 2 MB.',
            'kop_bawah.image' => 'Kop bawah harus berupa gambar PNG atau JPG.',
            'kop_bawah.mimes' => 'Kop bawah harus berformat PNG atau JPG.',
            'kop_bawah.max'   => 'Ukuran kop bawah maksimal 2 MB.',
        ];
    }
}

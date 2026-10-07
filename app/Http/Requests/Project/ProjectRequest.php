<?php

namespace App\Http\Requests\Project;

use App\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route()?->getActionMethod() === 'update' ? 'edit_project' : 'create_project';

        return $this->user()?->hasPermission('tracking_progress', $action) ?? false;
    }

    public function rules(): array
    {
        return [
            // Maks 25 karakter; nama lama yang lebih panjang boleh tetap selama tidak diubah.
            'name'        => ['required', 'string', function ($attribute, $value, $fail) {
                if (mb_strlen($value) > 25 && $value !== $this->route('project')?->name) {
                    $fail('Nama project maksimal 25 karakter.');
                }
            }],
            'client'      => ['required', 'string', 'max:255'],
            'contact_id'  => ['nullable', 'integer', 'exists:contacts,id'],
            'pic'         => ['required', 'string', 'max:255'],
            'company'     => ['nullable', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'category'    => [
                'required', 'string', 'max:100',
                // Harus ada di daftar Pengaturan, kecuali kategori yang sudah tersimpan di project ini.
                Rule::in(array_merge(AppSetting::projectCategories(), array_filter([$this->route('project')?->category]))),
            ],

            'event_date'       => ['required', 'date', 'after_or_equal:today'],
            'event_end_date'   => ['nullable', 'date', 'after_or_equal:event_date'],
            'event_time_start' => ['required', 'date_format:H:i,H:i:s'],
            'event_time_end'   => ['nullable', 'date_format:H:i,H:i:s', 'after:event_time_start'],

            'location' => ['required', 'string', 'max:255'],
            'address'  => ['required', 'string'],

            'estimated_duration_minutes' => ['required', 'integer', 'min:1', 'max:100000'],

            'priority' => ['required', 'in:Rendah,Normal,Tinggi'],

            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],

            'description' => ['nullable', 'string'],

            // Deadline internal (dipertahankan untuk kompatibilitas fitur lama)
            'deadline' => ['nullable', 'date'],
        ];
    }

    /**
     * Deadline lama otomatis mengikuti Tanggal Acara Mulai jika tidak dikirim,
     * agar fitur lama yang bergantung pada kolom "deadline" tetap konsisten.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('deadline') && $this->filled('event_date')) {
            $this->merge(['deadline' => $this->input('event_date')]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama project wajib diisi.',
            'name.max'          => 'Nama project maksimal 100 karakter agar rapi ditampilkan di tabel, kartu, dan laporan PDF.',
            'client.required'   => 'Nama client wajib diisi.',
            'pic.required'      => 'PIC wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'category.required' => 'Kategori project wajib dipilih.',
            'category.in'       => 'Kategori project tidak ada di daftar. Tambahkan lewat menu Pengaturan.',

            'event_date.required'       => 'Tanggal acara mulai wajib diisi.',
            'event_date.after_or_equal' => 'Tanggal acara mulai tidak boleh sebelum hari ini.',
            'event_end_date.after_or_equal' => 'Tanggal acara selesai tidak boleh sebelum tanggal acara mulai.',
            'event_time_start.required'    => 'Jam mulai acara wajib diisi.',
            'event_time_start.date_format' => 'Format jam mulai tidak valid.',
            'event_time_end.after'         => 'Jam selesai harus setelah jam mulai.',

            'location.required' => 'Lokasi/venue wajib diisi.',
            'address.required'  => 'Alamat lengkap wajib diisi.',

            'estimated_duration_minutes.required' => 'Estimasi durasi wajib diisi.',
            'estimated_duration_minutes.integer'  => 'Estimasi durasi harus berupa angka (menit).',
            'estimated_duration_minutes.min'      => 'Estimasi durasi minimal 1 menit.',

            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in'       => 'Prioritas tidak valid.',

            'estimated_value.numeric' => 'Estimasi pendapatan harus berupa angka.',
            'estimated_value.min'     => 'Estimasi pendapatan tidak boleh negatif.',
        ];
    }
}

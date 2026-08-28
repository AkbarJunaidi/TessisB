<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

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
            'name'        => ['required', 'string', 'max:100'],
            'client'      => ['required', 'string', 'max:255'],
            'pic'         => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],

            'event_date'       => ['required', 'date', 'after_or_equal:today'],
            'event_end_date'   => ['nullable', 'date', 'after_or_equal:event_date'],
            'event_time_start' => ['required', 'date_format:H:i,H:i:s'],
            'event_time_end'   => ['nullable', 'date_format:H:i,H:i:s', 'after:event_time_start'],

            'location' => ['required', 'string', 'max:255'],
            'address'  => ['required', 'string'],

            'estimated_duration_minutes' => ['required', 'integer', 'min:1', 'max:100000'],

            'priority' => ['required', 'in:Rendah,Normal,Tinggi'],

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
            'category.required' => 'Kategori project wajib dipilih.',

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
        ];
    }
}

<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AnnouncementStoreRequest extends FormRequest
{
    /**
     * Otorisasi fitur ini ditangani berlapis: role gate "super_admin" pada
     * route, lalu dicek ulang secara eksplisit di AnnouncementController
     * (mengikuti pola otorisasi ganda yang sama dengan
     * ActivityLogController::deleteRange()). Di sini cukup pastikan login.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Judul pengumuman wajib diisi.',
            'title.max'        => 'Judul maksimal 150 karakter.',
            'message.required' => 'Isi pengumuman wajib diisi.',
            'message.max'      => 'Isi pengumuman maksimal 2000 karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'   => 'Judul',
            'message' => 'Isi Pengumuman',
        ];
    }
}

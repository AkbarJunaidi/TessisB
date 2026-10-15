<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectNotifyRequest extends FormRequest
{
    /** Izin kirim dicek ulang di AnnouncementController::storeForProject(). */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'note'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Pilih minimal satu penerima.',
            'user_ids.min'      => 'Pilih minimal satu penerima.',
            'note.max'          => 'Catatan maksimal 500 karakter.',
        ];
    }
}

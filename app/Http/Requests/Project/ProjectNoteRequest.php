<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ProjectNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dilindungi auth + role middleware global
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'note'       => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Isi catatan tidak boleh kosong.',
            'note.min'      => 'Catatan minimal 3 karakter.',
            'note.max'      => 'Catatan maksimal 2000 karakter.',
        ];
    }
}

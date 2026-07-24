<?php

namespace App\Http\Requests\Task;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi data Task.
     */
    public function rules(): array
    {
        $allowedStatuses = Project::find($this->input('project_id'))
            ?->getBoardLists()
            ?? Project::DEFAULT_BOARD_LISTS;

        return [
            'project_id'  => ['required', 'exists:projects,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => [
                'required',
                Rule::in(array_column($allowedStatuses, 'label')),
            ],
            'priority'    => [
                'required',
                Rule::in(['Low', 'Medium', 'High']),
            ],
            'deadline'    => ['required', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Pesan validasi.
     */
    public function messages(): array
    {
        return [
            'project_id.required'   => 'Project wajib dipilih.',
            'project_id.exists'     => 'Project yang dipilih tidak ditemukan.',

            'title.required'        => 'Judul task wajib diisi.',
            'title.max'             => 'Judul task maksimal 255 karakter.',

            'description.string'    => 'Deskripsi task harus berupa teks.',

            'status.required'       => 'Status task wajib dipilih.',
            'status.in'             => 'Status yang dipilih tidak ditemukan pada board project ini.',

            'priority.required'     => 'Prioritas task wajib dipilih.',
            'priority.in'           => 'Prioritas harus berupa Low, Medium, atau High.',

            'deadline.required'     => 'Deadline wajib diisi.',
            'deadline.date'         => 'Format deadline tidak valid.',

            'assigned_to.exists'    => 'User yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Nama atribut agar lebih mudah dibaca pada pesan error.
     */
    public function attributes(): array
    {
        return [
            'project_id'  => 'Project',
            'title'       => 'Judul Task',
            'description' => 'Deskripsi',
            'status'      => 'Status',
            'priority'    => 'Prioritas',
            'deadline'    => 'Deadline',
            'assigned_to' => 'Assignee',
        ];
    }
}

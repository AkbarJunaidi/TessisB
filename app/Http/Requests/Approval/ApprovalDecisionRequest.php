<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ApprovalDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('approval', 'decide') ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}

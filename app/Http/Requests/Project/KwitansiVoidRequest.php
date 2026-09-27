<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class KwitansiVoidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('finance', 'void_kwitansi') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}

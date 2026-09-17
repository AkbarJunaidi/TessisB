<?php

namespace App\Http\Requests\Notification;

use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NotificationSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $validTypes = array_keys(NotificationService::TYPE_LABELS);

        return [
            'ordered_types'   => ['required', 'array'],
            'ordered_types.*' => ['required', 'string', Rule::in($validTypes)],
            'enabled_types'   => ['nullable', 'array'],
            'enabled_types.*' => ['string', Rule::in($validTypes)],
        ];
    }
}

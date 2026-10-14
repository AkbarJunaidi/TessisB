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

            'window.unpaid_deadline.before' => ['nullable', 'integer', 'min:0', 'max:30'],
            'window.unpaid_deadline.after'  => ['nullable', 'integer', 'min:0', 'max:30'],
            'window.finance_missing.before' => ['nullable', 'integer', 'min:0', 'max:27'],
        ];
    }

    public function messages(): array
    {
        return [
            'window.*.*.integer' => 'Jumlah hari harus berupa angka bulat.',
            'window.*.*.min'     => 'Jumlah hari tidak boleh kurang dari :min.',
            'window.*.*.max'     => 'Jumlah hari tidak boleh lebih dari :max.',
        ];
    }
}

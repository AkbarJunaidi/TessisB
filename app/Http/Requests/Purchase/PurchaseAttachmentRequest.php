<?php

namespace App\Http\Requests\Purchase;

use App\Models\PurchaseAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasPermission('purchase', 'edit') || $user?->hasPermission('purchase', 'pay')) ?? false;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::in(PurchaseAttachment::MANUAL_STAGES)],
            'file'  => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'stage.required' => 'Jenis lampiran wajib dipilih.',
            'file.required'  => 'Pilih file lampiran.',
            'file.mimes'     => 'Lampiran harus berupa JPG, PNG, WEBP, atau PDF.',
            'file.max'       => 'Ukuran lampiran maksimal 5MB.',
        ];
    }
}

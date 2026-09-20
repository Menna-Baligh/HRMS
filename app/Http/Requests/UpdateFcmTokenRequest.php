<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'fcm_token' => __('notifications.attributes.fcm_token'),
        ];
    }
}

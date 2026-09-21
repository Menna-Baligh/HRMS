<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth('api')->id();

        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
        ];
    }
}

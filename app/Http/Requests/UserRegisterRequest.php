<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,phone',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('auth.validation.name.required'),
            'name.string' => __('auth.validation.name.string'),
            'name.min' => __('auth.validation.name.min'),
            'name.max' => __('auth.validation.name.max'),

            'email.required' => __('auth.validation.email.required'),
            'email.email' => __('auth.validation.email.email'),
            'email.max' => __('auth.validation.email.max'),
            'email.unique' => __('auth.validation.email.unique'),

            'phone.string' => __('auth.validation.phone.string'),
            'phone.max' => __('auth.validation.phone.max'),
            'phone.unique' => __('auth.validation.phone.unique'),

            'password.required' => __('auth.validation.password.required'),
            'password.confirmed' => __('auth.validation.password.confirmed'),
        ];
    }
}
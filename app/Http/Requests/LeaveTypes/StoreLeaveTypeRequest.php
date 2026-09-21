<?php

namespace App\Http\Requests\LeaveTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
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
                'min:2',
                'max:255',
                'unique:leave_types,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],
            'default_days' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'requires_balance' => [
                'sometimes',
                'boolean',
            ],

            'requires_attachment' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
<?php

namespace App\Http\Requests\LeaveType;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gates handled by policy in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'requires_balance' => ['boolean'],
            'requires_attachment' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                'in:HR,Manager,Employee',
                function ($attribute, $value, $fail) {
                    if ($value === 'HR' && ! $this->user()?->hasRole('Owner')) {
                        $fail(__('employees.only_owner_can_hr'));
                    }
                },
            ],
            'job_title' => ['required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'employment_type' => ['required', 'in:Full-time,Part-time,Contract'],
            'start_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $manager = User::find($value);
                    if ($manager && ! $manager->hasRole('Manager')) {
                        $fail(__('employees.manager_must_be_manager_role'));
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'address' => ['nullable', 'string'],
            'company_location_id' => ['nullable', 'exists:company_locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),
            'role.in' => __('validation.in', ['attribute' => __('validation.attributes.role')]),
            'employment_type.in' => __('validation.in', ['attribute' => __('validation.attributes.employment_type')]),
            'manager_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.manager')]),
        ];
    }
}

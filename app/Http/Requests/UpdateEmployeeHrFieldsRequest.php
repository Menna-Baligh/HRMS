<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeHrFieldsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_title' => ['sometimes', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'in:Full-time,Part-time,Contract'],
            'status' => ['sometimes', 'in:active,inactive'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_title.string' => 'The job title must be a string.',
            'job_title.max' => 'The job title may not be greater than 255 characters.',
            'employment_type.in' => 'The selected employment type is invalid. It must be one of: Full-time, Part-time, Contract.',
            'status.in' => 'The selected status is invalid. It must be either active or inactive.',
            'department_id.exists' => 'The selected department does not exist.',
            'manager_id.exists' => 'The selected manager does not exist.',
        ];
    }
}

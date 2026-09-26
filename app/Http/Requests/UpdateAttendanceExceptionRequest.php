<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' => __('validation.attendance_exception.status_required'),
            'status.in' => __('validation.attendance_exception.status_in'),
            'admin_note.max' => __('validation.attendance_exception.admin_note_max'),
        ];
    }
}

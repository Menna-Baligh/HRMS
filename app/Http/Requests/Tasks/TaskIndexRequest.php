<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskIndexRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                Rule::enum(TaskStatus::class),
            ],

            'priority' => [
                'nullable',
                Rule::enum(TaskPriority::class),
            ],

            'deadline_from' => [
                'nullable',
                'date',
            ],

            'deadline_to' => [
                'nullable',
                'date',
                'after_or_equal:deadline_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}

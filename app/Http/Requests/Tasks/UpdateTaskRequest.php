<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
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
                //to update task

        return [
            'title' => ['sometimes','string','min:3','max:255',],

            'description' => ['sometimes','nullable','string',],

            'priority' => ['sometimes', Rule::enum(TaskPriority::class),],

            'deadline' => ['sometimes','date','after:now',],
        ];
    
}
}

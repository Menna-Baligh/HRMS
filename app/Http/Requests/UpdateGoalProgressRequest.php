<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $goal = $this->route('goal');

        return [
            'current_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($goal) {
                    if ($goal && $value > $goal->target_value) {
                        $fail("accepted value for {$attribute} must not exceed the target value of {$goal->target_value}.");
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $goalId = $this->route('id');
        $goal = Goal::find($goalId);

        return [
            'current_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($goal) {
                    if ($goal && $value > $goal->target_value) {
                        $fail(__('goal.errors.value_exceeds_target', [
                            'target' => $goal->target_value,
                        ]));
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_value' => __('goal.attributes.current_value'),
            'note' => __('goal.attributes.note'),
        ];
    }
}

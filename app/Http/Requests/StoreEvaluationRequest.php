<?php

namespace App\Http\Requests;

use App\Models\EvaluationCategory;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'period_id' => ['required', 'exists:evaluation_periods,id'],
            'feedback' => ['nullable', 'string'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.category_id' => ['required', 'exists:evaluation_categories,id'],
            'scores.*.score' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1] ?? null;
                    $categoryId = $this->input("scores.{$index}.category_id");

                    if ($categoryId) {
                        $category = EvaluationCategory::find($categoryId);
                        if ($category && $value > $category->max_score) {
                            $fail("The score for category '{$category->name}' cannot exceed {$category->max_score}.");
                        }
                    }
                },
            ],
            'evidence_goal_ids' => ['nullable', 'array'],
            'evidence_goal_ids.*' => ['exists:goals,id'],
        ];
    }
}

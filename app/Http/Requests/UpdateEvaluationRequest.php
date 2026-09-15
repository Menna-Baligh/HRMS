<?php

namespace App\Http\Requests;

use App\Models\EvaluationCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback' => ['nullable', 'string'],
            'scores' => ['sometimes', 'required', 'array', 'min:1'],
            'scores.*.category_id' => ['required_with:scores', 'exists:evaluation_categories,id'],
            'scores.*.score' => [
                'required_with:scores',
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

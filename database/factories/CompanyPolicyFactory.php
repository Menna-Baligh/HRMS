<?php

namespace Database\Factories;

use App\Models\CompanyPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPolicy>
 */
class CompanyPolicyFactory extends Factory
{
    protected $model = CompanyPolicy::class;

    public function definition(): array
    {
        return [
            'policy_code' => 'POL-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['Leave', 'Conduct', 'Benefits', 'Safety']),
            'section' => 'Section '.fake()->numberBetween(1, 10),
            'content' => fake()->paragraphs(2, true),
            'is_active' => true,
            'effective_date' => now()->subMonths(6)->toDateString(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

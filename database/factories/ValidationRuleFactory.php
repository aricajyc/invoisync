<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ValidationRule>
 */
class ValidationRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_code' => 'V' . $this->faker->unique()->numberBetween(100, 999),
            'rule_name' => $this->faker->words(3, true),
            'rule_description' => $this->faker->sentence(),
            'rule_type' => $this->faker->randomElement(['mandatory_field', 'format_check', 'business_logic', 'compliance']),
            'validation_expression' => null,
            'error_message_template' => 'Validation error',
            'is_active' => true,
            'priority' => 0,
        ];
    }
}

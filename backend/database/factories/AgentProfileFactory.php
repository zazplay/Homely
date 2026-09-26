<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Models\AgentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentProfile>
 */
class AgentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Real estate agent',
            'agency' => fake()->company(),
            'phone' => fake()->numerify('(###) 555-####'),
            'bio' => fake()->sentence(18),
            'license_number' => fake()->numerify('104########'),
            'is_verified' => true,
            'experience_years' => fake()->numberBetween(1, 15),
            'rating' => fake()->randomFloat(1, 4, 5),
            'reviews_count' => fake()->numberBetween(5, 90),
            'deals_count' => fake()->numberBetween(5, 150),
            'sales_volume_cents' => fake()->numberBetween(1, 100) * 1_000_000_00,
            'specializations' => [Specialization::Buying],
            'areas' => [ServiceArea::Brooklyn],
            'languages' => [Language::English],
            'cover_path' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['is_verified' => false]);
    }
}

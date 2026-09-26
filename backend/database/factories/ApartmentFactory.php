<?php

namespace Database\Factories;

use App\Enums\DealType;
use App\Enums\PropertyType;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalFloors = fake()->numberBetween(1, 25);

        return [
            'realtor_id' => User::factory()->realtor(),
            'title' => fake()->numberBetween(1, 4).'-room apartment, '.fake()->streetName(),
            'description' => fake()->sentence(15),
            'deal_type' => fake()->randomElement(DealType::cases()),
            'property_type' => PropertyType::Apartment,
            'bathrooms' => fake()->numberBetween(1, 3),
            'badge' => null,
            'is_new_build' => false,
            'price_cents' => fake()->numberBetween(300, 300_000) * 100,
            'city' => fake()->randomElement(['Kyiv', 'Lviv', 'Odesa', 'Dnipro']),
            'address' => fake()->streetAddress(),
            'rooms' => fake()->numberBetween(1, 5),
            'area' => fake()->randomFloat(2, 20, 200),
            'floor' => fake()->numberBetween(1, $totalFloors),
            'total_floors' => $totalFloors,
            'year_built' => null,
            'features' => [],
            'is_published' => true,
            'is_sold' => false,
        ];
    }

    public function sold(): static
    {
        return $this->state(fn () => ['is_sold' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}

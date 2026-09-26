<?php

use App\Enums\PropertyType;
use App\Models\Apartment;
use App\Models\User;

use function Pest\Laravel\getJson;

describe('catalog stats', function () {
    it('counts published listings per category in one response', function () {
        Apartment::factory()->create(['property_type' => PropertyType::Apartment]);
        Apartment::factory()->create(['property_type' => PropertyType::Loft, 'is_new_build' => true]);
        Apartment::factory()->create(['property_type' => PropertyType::House]);
        Apartment::factory()->create(['property_type' => PropertyType::Townhouse]);
        Apartment::factory()->create(['property_type' => PropertyType::Commercial]);
        Apartment::factory()->unpublished()->create(['property_type' => PropertyType::House]);

        getJson('/api/catalog/stats')->assertOk()->assertExactJson([
            'total' => 5,
            'apartments' => 2,
            'houses' => 2,
            'commercial' => 1,
            'new_builds' => 1,
        ]);
    });

    it('returns zeros for an empty catalog', function () {
        getJson('/api/catalog/stats')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('houses', 0);
    });
});

describe('catalog filters', function () {
    it('filters by category, location, new builds and minimum bedrooms', function () {
        Apartment::factory()->create(['property_type' => PropertyType::Townhouse, 'city' => 'Queens', 'address' => '17 Oak Ln, Astoria', 'rooms' => 3]);
        Apartment::factory()->create(['property_type' => PropertyType::House, 'city' => 'Montclair, NJ', 'rooms' => 4, 'is_new_build' => true]);
        Apartment::factory()->create(['property_type' => PropertyType::Apartment, 'city' => 'Queens', 'rooms' => 3]);
        Apartment::factory()->create(['property_type' => PropertyType::House, 'city' => 'Queens', 'rooms' => 1]);

        getJson('/api/apartments?category=houses&rooms_min=3')->assertJsonCount(2, 'data');
        getJson('/api/apartments?location=astoria')->assertJsonCount(1, 'data');
        getJson('/api/apartments?new_build=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.city', 'Montclair, NJ');
        getJson('/api/apartments?category=castles')->assertUnprocessable()->assertJsonValidationErrors('category');
    });

    it('returns the new listing fields', function () {
        Apartment::factory()->create(['property_type' => PropertyType::Loft, 'rooms' => 0, 'bathrooms' => 1, 'badge' => 'hot']);

        getJson('/api/apartments')
            ->assertJsonPath('data.0.property_type', 'loft')
            ->assertJsonPath('data.0.rooms', 0)
            ->assertJsonPath('data.0.bathrooms', 1)
            ->assertJsonPath('data.0.badge', 'hot')
            ->assertJsonPath('data.0.is_new_build', false)
            ->assertJsonStructure(['data' => [['realtor' => ['avatar_url', 'agency', 'rating', 'deals_count']]]]);
    });
});

describe('agents', function () {
    it('lists realtors ranked by deals with their published listings count', function () {
        $top = User::factory()->realtor(['deals_count' => 128, 'rating' => 4.9])->create();
        $second = User::factory()->realtor(['deals_count' => 96, 'rating' => 4.8])->create();
        User::factory()->create(); // a client — not an agent

        Apartment::factory()->count(2)->for($top, 'realtor')->create();
        Apartment::factory()->for($top, 'realtor')->unpublished()->create();
        Apartment::factory()->for($top, 'realtor')->sold()->create();

        getJson('/api/agents?limit=4')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $top->id)
            ->assertJsonPath('0.rating', 4.9)
            ->assertJsonPath('0.listings_count', 2)
            ->assertJsonPath('1.id', $second->id);
    });
});

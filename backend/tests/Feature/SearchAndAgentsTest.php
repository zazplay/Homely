<?php

use App\Enums\Feature;
use App\Enums\Language;
use App\Enums\PropertyType;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Models\AgentProfile;
use App\Models\Apartment;
use App\Models\Inquiry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

describe('search', function () {
    it('filters by several property types and requires all selected features', function () {
        Apartment::factory()->create(['title' => 'Loft', 'property_type' => PropertyType::Loft, 'features' => [Feature::Balcony, Feature::Elevator]]);
        Apartment::factory()->create(['title' => 'Flat', 'property_type' => PropertyType::Apartment, 'features' => [Feature::Balcony]]);
        Apartment::factory()->create(['title' => 'House', 'property_type' => PropertyType::House, 'features' => [Feature::Balcony, Feature::Elevator]]);

        getJson('/api/apartments?property_types=apartment,loft')->assertJsonCount(2, 'data');
        getJson('/api/apartments?property_types=apartment,loft&features=balcony,elevator')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Loft')
            ->assertJsonPath('data.0.features', ['balcony', 'elevator']);
        getJson('/api/apartments?features=balcony,swimming_pool')->assertUnprocessable()->assertJsonValidationErrors('features');
    });

    it('sorts by price and hides sold listings', function () {
        Apartment::factory()->create(['price_cents' => 300]);
        Apartment::factory()->create(['price_cents' => 100]);
        Apartment::factory()->create(['price_cents' => 200]);
        Apartment::factory()->sold()->create(['price_cents' => 50]);

        getJson('/api/apartments?sort=price_asc')->assertJsonCount(3, 'data')->assertJsonPath('data.0.price_cents', 100);
        getJson('/api/apartments?sort=price_desc')->assertJsonPath('data.0.price_cents', 300);
        getJson('/api/catalog/stats')->assertJsonPath('total', 3);
    });

    it('shows only listings of verified agents when asked', function () {
        Apartment::factory()->create(['title' => 'Verified']);
        $unverified = User::factory()->realtor(['is_verified' => false])->create();
        Apartment::factory()->for($unverified, 'realtor')->create(['title' => 'Unverified']);

        getJson('/api/apartments')->assertJsonCount(2, 'data');
        getJson('/api/apartments?verified=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Verified');
    });
});

describe('agents', function () {
    it('filters by text, specializations, areas, languages, rating and experience', function () {
        User::factory()->realtor([
            'agency' => 'Greenleaf Realty', 'rating' => 4.9, 'experience_years' => 12,
            'specializations' => [Specialization::Buying, Specialization::Selling],
            'areas' => [ServiceArea::Brooklyn], 'languages' => [Language::English, Language::Spanish],
        ])->create(['name' => 'Emma Carter']);
        User::factory()->realtor([
            'agency' => 'Metro Commercial', 'rating' => 4.6, 'experience_years' => 3,
            'specializations' => [Specialization::Commercial],
            'areas' => [ServiceArea::Manhattan, ServiceArea::NewJersey], 'languages' => [Language::English],
        ])->create(['name' => 'Noah Kim']);

        getJson('/api/agents?q=greenleaf')->assertJsonCount(1)->assertJsonPath('0.name', 'Emma Carter');
        getJson('/api/agents?q=new jersey')->assertJsonCount(1)->assertJsonPath('0.name', 'Noah Kim');
        getJson('/api/agents?specializations=buying,selling')->assertJsonCount(1);
        getJson('/api/agents?areas=brooklyn,manhattan')->assertJsonCount(2);
        getJson('/api/agents?languages=spanish')->assertJsonCount(1);
        getJson('/api/agents?min_rating=4.7')->assertJsonCount(1);
        getJson('/api/agents?min_experience=5')->assertJsonCount(1);
        getJson('/api/agents?sort=experience')->assertJsonPath('0.name', 'Emma Carter')
            ->assertJsonPath('0.specializations', ['buying', 'selling'])
            ->assertJsonPath('0.is_verified', true);
    });

    it('returns a full profile with reviews and listings, sold ones included', function () {
        $agent = User::factory()->realtor(['phone' => '(718) 555-0142'])->create();
        $agent->reviews()->create(['author_name' => 'Priya S.', 'deal_label' => 'Sold in Astoria', 'rating' => 5, 'body' => 'Great!']);
        Apartment::factory()->for($agent, 'realtor')->create();
        Apartment::factory()->for($agent, 'realtor')->sold()->create();
        Apartment::factory()->for($agent, 'realtor')->unpublished()->create();

        getJson("/api/agents/{$agent->id}")
            ->assertOk()
            ->assertJsonPath('agent.id', $agent->id)
            ->assertJsonPath('phone', '(718) 555-0142')
            ->assertJsonPath('reviews.0.author_name', 'Priya S.')
            ->assertJsonCount(2, 'listings');
    });

    it('does not expose clients as agents', function () {
        $client = User::factory()->create();

        getJson("/api/agents/{$client->id}")->assertNotFound();
    });
});

describe('inquiries', function () {
    it('sends a message about a listing to its realtor', function () {
        $apartment = Apartment::factory()->create();

        postJson('/api/inquiries', [
            'apartment_id' => $apartment->id,
            'name' => 'Daniel',
            'contact' => 'daniel@example.com',
            'message' => 'Is it still available?',
        ])->assertCreated();

        expect(Inquiry::sole())
            ->agent_id->toBe($apartment->realtor_id)
            ->apartment_id->toBe($apartment->id);
    });

    it('rejects messages about drafts and to non-agents', function () {
        $draft = Apartment::factory()->unpublished()->create();
        $client = User::factory()->create();
        $body = ['name' => 'Daniel', 'contact' => 'daniel@example.com', 'message' => 'Hello there'];

        postJson('/api/inquiries', [...$body, 'apartment_id' => $draft->id])->assertUnprocessable()->assertJsonValidationErrors('apartment_id');
        postJson('/api/inquiries', [...$body, 'agent_id' => $client->id])->assertUnprocessable()->assertJsonValidationErrors('agent_id');
        postJson('/api/inquiries', $body)->assertUnprocessable()->assertJsonValidationErrors(['apartment_id', 'agent_id']);
    });

    it('shows a realtor only their own leads', function () {
        $agent = User::factory()->realtor()->create();
        $other = User::factory()->realtor()->create();
        postJson('/api/inquiries', ['agent_id' => $agent->id, 'name' => 'Ann', 'contact' => 'ann@example.com', 'message' => 'Looking for a 2-bed']);
        postJson('/api/inquiries', ['agent_id' => $other->id, 'name' => 'Bob', 'contact' => 'bob@example.com', 'message' => 'Looking for a loft']);

        Sanctum::actingAs($agent);

        getJson('/api/my/inquiries')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Ann');
        postJson('/api/my/inquiries/'.Inquiry::firstWhere('name', 'Bob')->id.'/read')->assertNotFound();
    });
});

describe('verification', function () {
    it('creates an unverified agent profile when a realtor signs up', function () {
        postJson('/api/auth/register', [
            'name' => 'New Agent', 'email' => 'new@example.com', 'password' => 'secret123',
            'role' => 'realtor', 'agency' => 'Fresh Realty', 'license_number' => '10400000001',
        ])->assertCreated();

        expect(AgentProfile::sole())
            ->agency->toBe('Fresh Realty')
            ->license_number->toBe('10400000001')
            ->is_verified->toBeFalse();
    });

    it('keeps listings of an unverified realtor as drafts', function () {
        $realtor = User::factory()->realtor(['is_verified' => false])->create();
        Sanctum::actingAs($realtor);

        $id = postJson('/api/apartments', [
            'title' => 'My first listing', 'deal_type' => 'sale', 'price_cents' => 100000,
            'city' => 'Brooklyn', 'address' => 'Park Slope', 'rooms' => 1, 'area' => 40, 'is_published' => true,
        ])->assertCreated()->assertJsonPath('is_published', false)->json('id');

        patchJson("/api/apartments/{$id}", ['is_published' => true])->assertUnprocessable()->assertJsonValidationErrors('is_published');
    });

    it('marks a listing as sold', function () {
        $apartment = Apartment::factory()->create();
        Sanctum::actingAs($apartment->realtor);

        patchJson("/api/apartments/{$apartment->id}", ['is_sold' => true])->assertOk()->assertJsonPath('is_sold', true);
        getJson('/api/apartments')->assertJsonCount(0, 'data');
    });
});

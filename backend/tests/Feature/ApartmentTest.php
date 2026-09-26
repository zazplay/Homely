<?php

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Files go to a temporary fake disk instead of storage/app/public.
    Storage::fake('public');
});

/** A real PNG image encoded as a data URI. */
function base64Png(): string
{
    $file = UploadedFile::fake()->image('photo.png', 10, 10);

    return 'data:image/png;base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
}

function apartmentPayload(array $overrides = []): array
{
    return [
        'title' => '2-room apartment near the park',
        'deal_type' => 'rent',
        'price_cents' => 1_500_000,
        'city' => 'Kyiv',
        'address' => 'Khreshchatyk St, 1',
        'rooms' => 2,
        'area' => 54.3,
        'floor' => 5,
        'total_floors' => 9,
        ...$overrides,
    ];
}

describe('create', function () {
    it('lets a realtor create a listing with base64 photos stored as files', function () {
        $realtor = User::factory()->realtor()->create();
        Sanctum::actingAs($realtor);

        $response = postJson('/api/apartments', apartmentPayload(['photos' => [base64Png(), base64Png()]]))
            ->assertCreated()
            ->assertJsonPath('title', '2-room apartment near the park')
            ->assertJsonPath('deal_type', 'rent')
            ->assertJsonPath('area', 54.3)
            ->assertJsonPath('realtor.id', $realtor->id)
            ->assertJsonCount(2, 'photos');

        $apartment = Apartment::firstOrFail();
        $paths = $apartment->photos->pluck('path');

        // Files are on disk, the DB only has their paths.
        expect($paths)->toHaveCount(2);
        $paths->each(fn ($path) => Storage::disk('public')->assertExists($path));
        expect($paths->first())->toStartWith("apartments/{$apartment->id}/")->toEndWith('.png');

        // The response gives URLs, not base64.
        expect($response->json('photos.0.url'))->toContain('/storage/apartments/');
    });

    it('forbids clients from creating listings', function () {
        Sanctum::actingAs(User::factory()->create());

        postJson('/api/apartments', apartmentPayload())->assertForbidden();
    });

    it('requires authentication', function () {
        postJson('/api/apartments', apartmentPayload())->assertUnauthorized();
    });

    it('validates fields', function () {
        Sanctum::actingAs(User::factory()->realtor()->create());

        postJson('/api/apartments', ['title' => 'x', 'deal_type' => 'swap', 'rooms' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'deal_type', 'rooms', 'price_cents', 'city', 'address', 'area']);
    });

    it('rejects broken base64 and non-image files', function () {
        Sanctum::actingAs(User::factory()->realtor()->create());

        $phpScriptPretendingToBePng = 'data:image/png;base64,'.base64_encode('<?php echo "hacked";');

        postJson('/api/apartments', apartmentPayload(['photos' => [base64Png(), 'not-base64!!', $phpScriptPretendingToBePng]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos.1', 'photos.2'])
            ->assertJsonMissingValidationErrors('photos.0');

        expect(Apartment::count())->toBe(0);
        expect(Storage::disk('public')->allFiles())->toBeEmpty();
    });
});

describe('catalog', function () {
    it('shows only published listings to everyone', function () {
        Apartment::factory()->count(3)->create();
        Apartment::factory()->unpublished()->create();

        getJson('/api/apartments')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'price_cents', 'realtor' => ['id', 'name'], 'photos']], 'meta', 'links']);
    });

    it('filters by city, deal type, rooms and price', function () {
        Apartment::factory()->create(['city' => 'Kyiv', 'deal_type' => 'rent', 'rooms' => 2, 'price_cents' => 1_000_000]);
        Apartment::factory()->create(['city' => 'Kyiv', 'deal_type' => 'rent', 'rooms' => 2, 'price_cents' => 9_000_000]);
        Apartment::factory()->create(['city' => 'Kyiv', 'deal_type' => 'sale', 'rooms' => 2]);
        Apartment::factory()->create(['city' => 'Lviv', 'deal_type' => 'rent', 'rooms' => 2]);

        getJson('/api/apartments?city=kyiv&deal_type=rent&rooms=2&price_max=5000000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price_cents', 1_000_000);
    });

    it('hides an unpublished listing from others but shows it to its realtor', function () {
        $apartment = Apartment::factory()->unpublished()->create();

        getJson("/api/apartments/{$apartment->id}")->assertForbidden();

        Sanctum::actingAs($apartment->realtor);
        getJson("/api/apartments/{$apartment->id}")->assertOk();
    });

    it('lists the realtor\'s own listings including unpublished', function () {
        $realtor = User::factory()->realtor()->create();
        Apartment::factory()->for($realtor, 'realtor')->create();
        Apartment::factory()->for($realtor, 'realtor')->unpublished()->create();
        Apartment::factory()->create(); // someone else's

        Sanctum::actingAs($realtor);

        getJson('/api/my/apartments')->assertOk()->assertJsonCount(2, 'data');
    });
});

describe('update and delete', function () {
    it('lets the owner update only the sent fields', function () {
        $apartment = Apartment::factory()->create(['title' => 'Old title', 'rooms' => 3]);
        Sanctum::actingAs($apartment->realtor);

        patchJson("/api/apartments/{$apartment->id}", ['title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('title', 'New title')
            ->assertJsonPath('rooms', 3);
    });

    it('forbids another realtor from updating', function () {
        $apartment = Apartment::factory()->create();
        Sanctum::actingAs(User::factory()->realtor()->create());

        patchJson("/api/apartments/{$apartment->id}", ['title' => 'Hacked'])->assertForbidden();
    });

    it('lets an admin delete any listing and removes its files', function () {
        $realtor = User::factory()->realtor()->create();
        Sanctum::actingAs($realtor);
        $id = postJson('/api/apartments', apartmentPayload(['photos' => [base64Png()]]))->json('id');

        Sanctum::actingAs(User::factory()->admin()->create());
        deleteJson("/api/apartments/{$id}")->assertNoContent();

        expect(Apartment::find($id))->toBeNull();
        expect(Storage::disk('public')->allFiles())->toBeEmpty();
    });
});

describe('photos', function () {
    it('adds photos to an existing listing', function () {
        $apartment = Apartment::factory()->create();
        Sanctum::actingAs($apartment->realtor);

        postJson("/api/apartments/{$apartment->id}/photos", ['photos' => [base64Png()]])
            ->assertCreated()
            ->assertJsonCount(1, 'photos');
    });

    it('deletes a photo together with its file', function () {
        $realtor = User::factory()->realtor()->create();
        Sanctum::actingAs($realtor);
        $apartment = postJson('/api/apartments', apartmentPayload(['photos' => [base64Png()]]))->json();

        deleteJson("/api/apartments/{$apartment['id']}/photos/{$apartment['photos'][0]['id']}")->assertNoContent();

        expect(Storage::disk('public')->allFiles())->toBeEmpty();
    });

    it('returns 404 for a photo of another apartment', function () {
        $realtor = User::factory()->realtor()->create();
        Sanctum::actingAs($realtor);
        $first = postJson('/api/apartments', apartmentPayload(['photos' => [base64Png()]]))->json();
        $second = postJson('/api/apartments', apartmentPayload())->json();

        deleteJson("/api/apartments/{$second['id']}/photos/{$first['photos'][0]['id']}")->assertNotFound();
    });
});

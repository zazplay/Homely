<?php

namespace Database\Seeders;

use App\Actions\Apartments\StoreApartmentPhotos;
use App\Enums\DealType;
use App\Enums\Feature as F;
use App\Enums\Language as L;
use App\Enums\ListingBadge;
use App\Enums\PropertyType;
use App\Enums\ServiceArea as A;
use App\Enums\Specialization as S;
use App\Models\Apartment;
use App\Models\User;
use App\Support\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Agents, reviews and listings from the "Homely" design mockup, with its photos.
 * Photos go through StoreApartmentPhotos — the same path as a base64 upload from the API.
 * Password for every agent: "password".
 */
class HomelySeeder extends Seeder
{
    private const IMAGES = __DIR__.'/homely';

    /** Extra gallery shots rotated into residential listings. */
    private const GALLERY = [
        '1552321554-5fefe8c9ef14',  // bathroom
        '1502672260266-1c1ef2d93688', // living room
        '1522708323590-d24dbb6b0267',
        '1505693416388-ac5ce068fe85',
        '1484154218962-a197022b5858',
        '1493809842364-78817add7ffb',
    ];

    public function run(StoreApartmentPhotos $storePhotos): void
    {
        $agents = [];

        foreach ($this->agents() as $data) {
            $agent = User::factory()->realtor()->create([
                'name' => $data['name'],
                'email' => Str::slug($data['name'], '.').'@example.com',
                'avatar_path' => $this->storeImage($data['photo'], 'avatars'),
            ]);

            // Aggregates (rating, deals…) aren't mass-assignable — they come from other systems.
            $agent->agentProfile->forceFill([
                'title' => $data['title'] ?? 'Real estate agent',
                'agency' => $data['agency'],
                'phone' => $data['phone'],
                'bio' => $data['bio'],
                'license_number' => $data['license'],
                'is_verified' => true,
                'experience_years' => $data['exp'],
                'rating' => $data['rating'],
                'reviews_count' => $data['reviews'],
                'deals_count' => $data['deals'],
                'sales_volume_cents' => $data['volume'] * 100,
                'specializations' => $data['specs'],
                'areas' => $data['areas'],
                'languages' => $data['langs'],
                'cover_path' => isset($data['cover']) ? $this->storeImage($data['cover'], 'covers') : null,
            ])->save();

            foreach ($data['review_texts'] as $review) {
                $agent->reviews()->create($review);
            }

            $agents[$data['name']] = $agent;
        }

        foreach ($this->listings() as $index => $listing) {
            $createdAt = now()->subHours($listing['hours_ago']);

            $apartment = Apartment::factory()->for($agents[$listing['agent']], 'realtor')->create([
                'title' => $listing['title'],
                'description' => $listing['description'],
                'deal_type' => $listing['rent'] ? DealType::Rent : DealType::Sale,
                'property_type' => $listing['type'],
                // Mockup prices are in dollars; the API stores cents.
                'price_cents' => ($listing['rent'] ?? $listing['sale']) * 100,
                'city' => $listing['city'],
                'address' => $listing['address'],
                'rooms' => $listing['beds'],
                'bathrooms' => $listing['baths'],
                'area' => round($listing['sqft'] * 0.092903, 1), // sq ft -> m²
                'floor' => $listing['floor'][0] ?? null,
                'total_floors' => $listing['floor'][1] ?? null,
                'year_built' => $listing['built'] ?? null,
                'features' => $listing['features'],
                'badge' => $listing['badge'],
                'is_new_build' => $listing['new_build'] ?? false,
                'is_published' => true,
                'is_sold' => $listing['sold'] ?? false,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $storePhotos->handle($apartment, array_map($this->dataUri(...), $this->photosFor($listing, $index)));
        }
    }

    /**
     * @param  array{photo: string, type: PropertyType, gallery?: list<string>}  $listing
     * @return list<string>
     */
    private function photosFor(array $listing, int $index): array
    {
        if (isset($listing['gallery'])) {
            return [$listing['photo'], ...$listing['gallery']];
        }

        if ($listing['type'] === PropertyType::Commercial) {
            return [$listing['photo'], '1497366216548-37526070297c'];
        }

        $extras = array_values(array_diff(self::GALLERY, [$listing['photo']]));

        return [$listing['photo'], $extras[0], $extras[1 + $index % (count($extras) - 1)]];
    }

    private function dataUri(string $photo): string
    {
        return 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents(self::IMAGES."/{$photo}.jpg"));
    }

    private function storeImage(string $photo, string $directory): string
    {
        $path = $directory.'/'.Str::uuid().'.jpg';
        Media::disk()->put($path, (string) file_get_contents(self::IMAGES."/{$photo}.jpg"));

        return $path;
    }

    /**
     * From Agents.dc.html (+ Agent.dc.html for Emma Carter's full profile).
     *
     * @return list<array<string, mixed>>
     */
    private function agents(): array
    {
        $generic = fn (string $name, string $deal, string $text) => ['author_name' => $name, 'deal_label' => $deal, 'rating' => 5, 'body' => $text];

        return [
            [
                'name' => 'Emma Carter', 'title' => 'Senior agent', 'agency' => 'Greenleaf Realty', 'photo' => '1494790108377-be9c29b29330', 'cover' => '1545324418-cc1a3fa10c00',
                'rating' => 4.9, 'reviews' => 86, 'deals' => 128, 'exp' => 12, 'volume' => 92_000_000,
                'specs' => [S::Buying, S::Selling], 'areas' => [A::Brooklyn, A::Queens], 'langs' => [L::English, L::Spanish],
                'phone' => '(718) 555-0142', 'license' => '10401234567',
                'bio' => '12 years helping families buy and sell across Brooklyn. Specializes in pre-war co-ops, first-time buyers and relocation.',
                'review_texts' => [
                    $generic('Daniel & Mia R.', 'Bought in Park Slope', 'Emma found us an off-market 3-bed two weeks after our first call and negotiated $40k off the asking price. Calm, fast and honest the whole way.'),
                    $generic('Priya S.', 'Sold in Astoria', 'Staging advice, great photos and 14 showings in the first weekend. We closed above ask in under a month.'),
                    $generic('Marcus L.', 'Rented in DUMBO', "Relocating from Chicago, I never saw the apartment in person. Emma's video tours were thorough and everything was exactly as described."),
                ],
            ],
            [
                'name' => 'Liam Brooks', 'agency' => 'Brooks & Co.', 'photo' => '1507003211169-0a1dd7228f2d',
                'rating' => 4.8, 'reviews' => 64, 'deals' => 96, 'exp' => 9, 'volume' => 118_000_000,
                'specs' => [S::Selling, S::Luxury], 'areas' => [A::NewJersey, A::Manhattan], 'langs' => [L::English],
                'phone' => '(201) 555-0187', 'license' => '10401298765',
                'bio' => 'Luxury homes and family houses in Manhattan and North Jersey. Known for sharp pricing and quiet off-market sales.',
                'review_texts' => [$generic('Karen T.', 'Sold in Montclair', 'Liam priced our house perfectly — three offers in the first week and a smooth closing.')],
            ],
            [
                'name' => 'Sofia Nguyen', 'agency' => 'Harbor Homes', 'photo' => '1438761681033-6461ffad8d80',
                'rating' => 5.0, 'reviews' => 41, 'deals' => 74, 'exp' => 6, 'volume' => 38_000_000,
                'specs' => [S::Rentals, S::Buying], 'areas' => [A::Brooklyn], 'langs' => [L::English, L::French],
                'phone' => '(718) 555-0199', 'license' => '10401345678',
                'bio' => 'Rentals and first purchases along the Brooklyn waterfront. Fast replies, honest advice, lots of video tours.',
                'review_texts' => [$generic('Julien M.', 'Rented in DUMBO', 'Sofia had three great lofts lined up for me in one afternoon. Signed the lease the same week.')],
            ],
            [
                'name' => 'Noah Kim', 'agency' => 'Metro Commercial', 'photo' => '1500648767791-00dcc994a43e',
                'rating' => 4.9, 'reviews' => 38, 'deals' => 61, 'exp' => 11, 'volume' => 140_000_000,
                'specs' => [S::Commercial], 'areas' => [A::Brooklyn, A::Manhattan], 'langs' => [L::English, L::Chinese],
                'phone' => '(212) 555-0163', 'license' => '10401456789',
                'bio' => 'Retail and office space in Brooklyn and Manhattan. Helps small businesses find the right storefront.',
                'review_texts' => [$generic('Lena & Co. Bakery', 'Leased in Williamsburg', 'Noah understood foot traffic better than anyone we talked to. Our corner spot was the right call.')],
            ],
            [
                'name' => 'Olivia Reed', 'agency' => 'Skyline Partners', 'photo' => '1544005313-94ddf0286df2',
                'rating' => 4.7, 'reviews' => 52, 'deals' => 83, 'exp' => 8, 'volume' => 64_000_000,
                'specs' => [S::Rentals, S::Luxury], 'areas' => [A::Manhattan], 'langs' => [L::English, L::French],
                'phone' => '(212) 555-0121', 'license' => '10401567890',
                'bio' => 'High-rise rentals and luxury condos in Midtown and the Upper West Side.',
                'review_texts' => [$generic('Tom H.', 'Rented in Midtown', 'Great views, great building, zero surprises. Olivia handled the board package for us.')],
            ],
            [
                'name' => 'Daniel Ortiz', 'agency' => 'Borough Realty', 'photo' => '1472099645785-5658abf4ff4e',
                'rating' => 4.6, 'reviews' => 29, 'deals' => 45, 'exp' => 4, 'volume' => 26_000_000,
                'specs' => [S::Buying, S::Rentals], 'areas' => [A::Bronx, A::Queens], 'langs' => [L::English, L::Spanish],
                'phone' => '(347) 555-0174', 'license' => '10401678901',
                'bio' => 'Bronx and Queens specialist. Bilingual, patient with first-time buyers.',
                'review_texts' => [$generic('Ana P.', 'Bought in Queens', 'Daniel explained every step in Spanish for my parents. We felt safe the whole time.')],
            ],
            [
                'name' => 'Anna Volkova', 'agency' => 'Brighton Estates', 'photo' => '1534528741775-53994a69daeb',
                'rating' => 4.9, 'reviews' => 70, 'deals' => 112, 'exp' => 14, 'volume' => 105_000_000,
                'specs' => [S::Buying, S::Selling, S::NewBuilds], 'areas' => [A::Brooklyn], 'langs' => [L::English, L::Russian],
                'phone' => '(718) 555-0156', 'license' => '10401789012',
                'bio' => 'New developments and family homes. 14 years in Brooklyn, works directly with builders.',
                'review_texts' => [$generic('Igor & Maria K.', 'Bought a new build', 'Anna got us into the development before public launch and negotiated upgrades for free.')],
            ],
            [
                'name' => 'James Park', 'agency' => 'Hudson Living', 'photo' => '1506794778202-cad84cf45f1d',
                'rating' => 4.5, 'reviews' => 22, 'deals' => 30, 'exp' => 3, 'volume' => 18_000_000,
                'specs' => [S::NewBuilds, S::Buying], 'areas' => [A::NewJersey], 'langs' => [L::English, L::Chinese],
                'phone' => '(201) 555-0132', 'license' => '10401890123',
                'bio' => 'New builds along the Jersey waterfront. Great for commuters to Manhattan.',
                'review_texts' => [$generic('Wei L.', 'Bought in Jersey City', 'James knew every building on the waterfront and their HOA details by heart.')],
            ],
            [
                'name' => 'Grace Miller', 'agency' => 'Queensway Homes', 'photo' => '1580489944761-15a19d654956',
                'rating' => 4.8, 'reviews' => 47, 'deals' => 68, 'exp' => 7, 'volume' => 41_000_000,
                'specs' => [S::Selling, S::Rentals], 'areas' => [A::Queens], 'langs' => [L::English],
                'phone' => '(718) 555-0118', 'license' => '10401901234',
                'bio' => 'Selling and renting across Queens — Astoria, LIC and Forest Hills.',
                'review_texts' => [$generic('Rob D.', 'Sold in Forest Hills', 'Grace sold our apartment in 9 days. Her staging tips made a huge difference.')],
            ],
        ];
    }

    /**
     * From Search.dc.html (types, beds, sq ft, prices, features), Homely.dc.html (badges, addresses)
     * and Agent.dc.html (sold listings). hours_ago controls the "Fresh listings" order.
     *
     * @return list<array<string, mixed>>
     */
    private function listings(): array
    {
        return [
            [
                'title' => 'Sunny 3-bed with balcony', 'address' => '214 7th Ave, Park Slope', 'city' => 'Brooklyn',
                'type' => PropertyType::Apartment, 'beds' => 3, 'baths' => 2, 'sqft' => 1420, 'sale' => 785000, 'rent' => null,
                'floor' => [4, 6], 'built' => 1928, 'badge' => ListingBadge::New, 'agent' => 'Emma Carter', 'hours_ago' => 1,
                'features' => [F::Balcony, F::Elevator, F::InUnitLaundry, F::CentralAc, F::Dishwasher, F::PetFriendly, F::BikeStorage, F::SouthFacing],
                'photo' => '1560448204-e02f11c3d0e2',
                'gallery' => ['1502672260266-1c1ef2d93688', '1484154218962-a197022b5858', '1505693416388-ac5ce068fe85', '1522708323590-d24dbb6b0267', '1552321554-5fefe8c9ef14', '1493809842364-78817add7ffb'],
                'description' => "Bright corner unit on the 4th floor of a pre-war elevator building, fully renovated in 2024. Floor-to-ceiling south-facing windows, white-oak floors, and an open kitchen with quartz counters and Bosch appliances.\n\nThe private 80 sq ft balcony overlooks tree-lined 7th Avenue. Two blocks to Prospect Park and 5 minutes to the F/G at 7th Ave station.",
            ],
            [
                'title' => 'Modern family house with garden', 'address' => '58 Maple Rd', 'city' => 'Montclair, NJ',
                'type' => PropertyType::House, 'beds' => 4, 'baths' => 3, 'sqft' => 2650, 'sale' => 1240000, 'rent' => null,
                'built' => 2019, 'badge' => ListingBadge::Hot, 'agent' => 'Liam Brooks', 'photo' => '1600596542815-ffad4c1539a9', 'hours_ago' => 2,
                'features' => [F::Parking, F::Garden, F::PetFriendly, F::CentralAc, F::Dishwasher],
                'description' => "Fully renovated family home on a quiet tree-lined street. Landscaped garden, two-car driveway and a finished basement.\n\nTop-rated schools nearby.",
            ],
            [
                'title' => 'Loft studio near the river', 'address' => '9 Water St, DUMBO', 'city' => 'Brooklyn',
                'type' => PropertyType::Loft, 'beds' => 0, 'baths' => 1, 'sqft' => 720, 'sale' => 690000, 'rent' => 3400,
                'floor' => [3, 6], 'built' => 1915, 'badge' => null, 'agent' => 'Sofia Nguyen', 'photo' => '1493809842364-78817add7ffb', 'hours_ago' => 3,
                'features' => [F::Elevator, F::Doorman, F::BikeStorage],
                'description' => "Converted warehouse loft with 12 ft ceilings, exposed brick and oversized windows.\n\nSteps from Brooklyn Bridge Park and the East River ferry.",
            ],
            [
                'title' => 'Corner retail space, high foot traffic', 'address' => '301 Bedford Ave, Williamsburg', 'city' => 'Brooklyn',
                'type' => PropertyType::Commercial, 'beds' => 0, 'baths' => null, 'sqft' => 1900, 'sale' => 2100000, 'rent' => null,
                'built' => 1931, 'badge' => ListingBadge::New, 'agent' => 'Noah Kim', 'photo' => '1441986300917-64674bd600d8', 'hours_ago' => 4,
                'features' => [],
                'description' => 'Ground-floor corner storefront with 40 ft of frontage on Bedford Ave. Full basement storage, zoned for retail and food service.',
            ],
            [
                'title' => 'Cozy townhouse, renovated kitchen', 'address' => '17 Oak Ln, Astoria', 'city' => 'Queens',
                'type' => PropertyType::Townhouse, 'beds' => 3, 'baths' => 2, 'sqft' => 1580, 'sale' => 659000, 'rent' => null,
                'built' => 1940, 'badge' => ListingBadge::PriceDrop, 'agent' => 'Emma Carter', 'photo' => '1484154218962-a197022b5858', 'hours_ago' => 5,
                'features' => [F::Garden, F::PetFriendly, F::Dishwasher],
                'description' => "Two-level townhouse with a brand-new chef's kitchen, private backyard and a finished attic office.\n\nClose to Astoria Park and the N/W trains.",
            ],
            [
                'title' => '2-bed with skyline views', 'address' => '440 W 42nd St, Midtown', 'city' => 'Manhattan',
                'type' => PropertyType::Apartment, 'beds' => 2, 'baths' => 1, 'sqft' => 980, 'sale' => 1150000, 'rent' => 2850,
                'floor' => [32, 45], 'built' => 2008, 'badge' => null, 'agent' => 'Olivia Reed', 'photo' => '1502005229762-cf1b2da7c5d6', 'hours_ago' => 6,
                'features' => [F::Elevator, F::Doorman, F::Balcony, F::Gym],
                'description' => 'High-floor apartment with floor-to-ceiling windows facing the Hudson. Doorman, gym and roof deck in the building.',
            ],
            [
                'title' => 'Bright 2-bed near the park', 'address' => 'Windsor Terrace', 'city' => 'Brooklyn',
                'type' => PropertyType::Apartment, 'beds' => 2, 'baths' => 1, 'sqft' => 1010, 'sale' => 712000, 'rent' => null,
                'floor' => [2, 4], 'built' => 1925, 'badge' => null, 'agent' => 'Emma Carter', 'photo' => '1522708323590-d24dbb6b0267', 'hours_ago' => 20,
                'features' => [F::PetFriendly],
                'description' => 'Sunny two-bedroom a block from Prospect Park. Hardwood floors, renovated bath, low maintenance.',
            ],
            [
                'title' => 'Brownstone parlor floor', 'address' => 'Carroll Gardens', 'city' => 'Brooklyn',
                'type' => PropertyType::Townhouse, 'beds' => 3, 'baths' => 2, 'sqft' => 1900, 'sale' => 1580000, 'rent' => null,
                'built' => 1899, 'badge' => null, 'sold' => true, 'agent' => 'Emma Carter', 'photo' => '1570129477492-45c003edd2be', 'hours_ago' => 30,
                'features' => [F::Garden, F::Balcony],
                'description' => 'Classic brownstone parlor floor with original mouldings, marble fireplaces and a private garden.',
            ],
            [
                'title' => 'Pre-war 1-bed co-op', 'address' => 'Prospect Heights', 'city' => 'Brooklyn',
                'type' => PropertyType::Apartment, 'beds' => 1, 'baths' => 1, 'sqft' => 640, 'sale' => 540000, 'rent' => null,
                'floor' => [3, 6], 'built' => 1931, 'badge' => null, 'sold' => true, 'agent' => 'Emma Carter', 'photo' => '1502672260266-1c1ef2d93688', 'hours_ago' => 40,
                'features' => [F::Elevator],
                'description' => 'Charming pre-war co-op with high ceilings and arched doorways. Pet-friendly building with an elevator.',
            ],
            [
                'title' => 'New build villa, 2-car garage', 'address' => 'Fox Meadow', 'city' => 'Scarsdale, NY',
                'type' => PropertyType::House, 'beds' => 5, 'baths' => 4, 'sqft' => 3400, 'sale' => 1890000, 'rent' => null,
                'built' => 2025, 'badge' => ListingBadge::New, 'new_build' => true, 'agent' => 'Anna Volkova', 'photo' => '1600585154340-be6161a56a0c', 'hours_ago' => 50,
                'features' => [F::Parking, F::Garden, F::PetFriendly, F::CentralAc],
                'description' => 'Brand-new five-bedroom villa with smart-home wiring, radiant floors, a heated pool and a two-car garage.',
            ],
            [
                'title' => 'Doorman 1-bed, gym & roof', 'address' => 'Upper West Side', 'city' => 'Manhattan',
                'type' => PropertyType::Apartment, 'beds' => 1, 'baths' => 1, 'sqft' => 760, 'sale' => 895000, 'rent' => 3650,
                'floor' => [12, 20], 'built' => 1962, 'badge' => null, 'agent' => 'Olivia Reed', 'photo' => '1545324418-cc1a3fa10c00', 'hours_ago' => 60,
                'features' => [F::Doorman, F::Elevator, F::PetFriendly, F::Gym],
                'description' => 'Full-service building with a 24h doorman, gym and landscaped roof deck. Two blocks from Central Park.',
            ],
            [
                'title' => 'Garden duplex, 3 bed', 'address' => 'Fort Greene', 'city' => 'Brooklyn',
                'type' => PropertyType::Townhouse, 'beds' => 3, 'baths' => 2, 'sqft' => 1650, 'sale' => 1320000, 'rent' => null,
                'built' => 1905, 'badge' => null, 'agent' => 'Daniel Ortiz', 'photo' => '1505693416388-ac5ce068fe85', 'hours_ago' => 72,
                'features' => [F::Garden, F::PetFriendly, F::Balcony, F::InUnitLaundry],
                'description' => 'Garden-level duplex with a private patio, open living space and a washer/dryer. Near Fort Greene Park.',
            ],
        ];
    }
}

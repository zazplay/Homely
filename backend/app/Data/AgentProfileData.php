<?php

namespace App\Data;

use App\Data\Apartments\ApartmentData;
use App\Models\AgentReview;
use App\Models\Apartment;
use App\Models\User;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Full public profile page of a realtor.
 */
#[MapName(SnakeCaseMapper::class)]
class AgentProfileData extends Data
{
    /**
     * @param  list<ReviewData>  $reviews
     * @param  list<ApartmentData>  $listings  published listings, sold ones included (the "Sold" tab)
     */
    public function __construct(
        public AgentData $agent,
        public ?string $phone,
        public ?string $bio,
        public ?string $licenseNumber,
        public int $salesVolumeCents,
        public ?string $coverUrl,
        public array $reviews,
        public array $listings,
    ) {}

    /**
     * @param  iterable<AgentReview>  $reviews
     * @param  iterable<Apartment>  $listings
     */
    public static function build(User $agent, iterable $reviews, iterable $listings): self
    {
        $profile = $agent->agentProfile;

        return new self(
            agent: AgentData::fromModel($agent),
            phone: $profile?->phone,
            bio: $profile?->bio,
            licenseNumber: $profile?->license_number,
            salesVolumeCents: $profile->sales_volume_cents ?? 0,
            coverUrl: $profile?->cover_url,
            reviews: collect($reviews)->map(ReviewData::fromModel(...))->values()->all(),
            listings: collect($listings)->map(ApartmentData::fromModel(...))->values()->all(),
        );
    }
}

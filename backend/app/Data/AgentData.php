<?php

namespace App\Data;

use App\Enums\Language;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Models\User;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Public realtor card: agents list, listing cards, listing page sidebar.
 * Contact details (phone, email) are not here — only on the full profile.
 */
#[MapName(SnakeCaseMapper::class)]
class AgentData extends Data
{
    /**
     * @param  list<Specialization>  $specializations
     * @param  list<ServiceArea>  $areas
     * @param  list<Language>  $languages
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $avatarUrl,
        public ?string $title,
        public ?string $agency,
        public bool $isVerified,
        public ?float $rating,
        public int $reviewsCount,
        public int $dealsCount,
        public int $experienceYears,
        public array $specializations,
        public array $areas,
        public array $languages,
        /** Only when the query used withCount(... as listings_count). */
        public ?int $listingsCount,
    ) {}

    /**
     * Expects "agentProfile" to be eager-loaded. Admins who post listings have no profile — defaults are used.
     */
    public static function fromModel(User $user): self
    {
        $profile = $user->agentProfile;

        return new self(
            id: $user->id,
            name: $user->name,
            avatarUrl: $user->avatar_url,
            title: $profile?->title,
            agency: $profile?->agency,
            isVerified: $profile->is_verified ?? false,
            rating: $profile?->rating !== null ? (float) $profile->rating : null,
            reviewsCount: $profile->reviews_count ?? 0,
            dealsCount: $profile->deals_count ?? 0,
            experienceYears: $profile->experience_years ?? 0,
            specializations: $profile?->specializations->values()->all() ?? [],
            areas: $profile?->areas->values()->all() ?? [],
            languages: $profile?->languages->values()->all() ?? [],
            listingsCount: array_key_exists('listings_count', $user->getAttributes()) ? (int) $user->getAttribute('listings_count') : null,
        );
    }
}

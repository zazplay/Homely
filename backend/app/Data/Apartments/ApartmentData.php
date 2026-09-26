<?php

namespace App\Data\Apartments;

use App\Data\AgentData;
use App\Enums\DealType;
use App\Enums\Feature;
use App\Enums\ListingBadge;
use App\Enums\PropertyType;
use App\Models\Apartment;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Public shape of an apartment listing. Photos are URLs, never base64.
 */
#[MapName(SnakeCaseMapper::class)]
class ApartmentData extends Data
{
    /**
     * @param  list<Feature>  $features
     * @param  list<PhotoData>  $photos
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public DealType $dealType,
        public PropertyType $propertyType,
        public int $priceCents,
        public string $city,
        public string $address,
        public int $rooms,
        public ?int $bathrooms,
        public float $area,
        public ?int $floor,
        public ?int $totalFloors,
        public ?int $yearBuilt,
        public array $features,
        public bool $isPublished,
        public bool $isSold,
        public ?ListingBadge $badge,
        public bool $isNewBuild,
        public AgentData $realtor,
        public array $photos,
        public CarbonImmutable $createdAt,
    ) {}

    /**
     * Relations that must be eager-loaded before fromModel() (strict mode forbids lazy loading).
     */
    public const RELATIONS = ['realtor.agentProfile', 'photos'];

    public static function fromModel(Apartment $apartment): self
    {
        return new self(
            id: $apartment->id,
            title: $apartment->title,
            description: $apartment->description,
            dealType: $apartment->deal_type,
            propertyType: $apartment->property_type,
            priceCents: $apartment->price_cents,
            city: $apartment->city,
            address: $apartment->address,
            rooms: $apartment->rooms,
            bathrooms: $apartment->bathrooms,
            area: (float) $apartment->area,
            floor: $apartment->floor,
            totalFloors: $apartment->total_floors,
            yearBuilt: $apartment->year_built,
            features: $apartment->features?->values()->all() ?? [],
            isPublished: $apartment->is_published,
            isSold: $apartment->is_sold,
            badge: $apartment->badge,
            isNewBuild: $apartment->is_new_build,
            realtor: AgentData::fromModel($apartment->realtor),
            photos: $apartment->photos->map(PhotoData::fromModel(...))->values()->all(),
            createdAt: $apartment->created_at,
        );
    }
}

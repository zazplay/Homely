<?php

namespace App\Data\Apartments;

use App\Enums\DealType;
use App\Enums\Feature;
use App\Enums\PropertyType;
use App\Rules\EnumList;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Catalog filters from the query string, e.g.
 * ?location=brooklyn&deal_type=rent&property_types=apartment,loft&features=balcony&rooms_min=2&price_max=500000&sort=price_asc
 */
#[MapName(SnakeCaseMapper::class)]
class ApartmentFilterData extends Data
{
    public function __construct(
        #[Max(100)]
        public ?string $city = null,

        /** Matches city OR address (neighborhood, street). */
        #[Max(100)]
        public ?string $location = null,

        public ?DealType $dealType = null,

        public ?PropertyType $propertyType = null,

        /** Comma-separated list: any of them. */
        public ?string $propertyTypes = null,

        /** Home page category: a group of property types (see PropertyType::categories()). */
        #[In('apartments', 'houses', 'commercial')]
        public ?string $category = null,

        /** Comma-separated list: the listing must have all of them. */
        public ?string $features = null,

        /** Only listings of license-verified realtors. */
        public ?bool $verified = null,

        public ?bool $newBuild = null,

        #[Between(0, 20)]
        public ?int $rooms = null,

        #[Between(0, 20)]
        public ?int $roomsMin = null,

        #[Min(0)]
        public ?int $priceMin = null,

        #[Min(0)]
        public ?int $priceMax = null,

        /** When set, only listings of this realtor. */
        public ?int $realtorId = null,

        #[In('newest', 'price_asc', 'price_desc')]
        public string $sort = 'newest',

        #[Between(1, 100)]
        public int $perPage = 15,
    ) {}

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'property_types' => ['nullable', new EnumList(PropertyType::class)],
            'features' => ['nullable', new EnumList(Feature::class)],
        ];
    }

    /**
     * @return list<PropertyType>
     */
    public function propertyTypeList(): array
    {
        return EnumList::parse($this->propertyTypes, PropertyType::class);
    }

    /**
     * @return list<Feature>
     */
    public function featureList(): array
    {
        return EnumList::parse($this->features, Feature::class);
    }
}

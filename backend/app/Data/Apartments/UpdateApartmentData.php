<?php

namespace App\Data\Apartments;

use App\Enums\DealType;
use App\Enums\Feature;
use App\Enums\ListingBadge;
use App\Enums\PropertyType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Partial update (PATCH): Optional = "field not sent, leave as is"; null = "clear the field".
 * Photos are managed by their own endpoints.
 */
#[MapName(SnakeCaseMapper::class)]
class UpdateApartmentData extends Data
{
    /**
     * @param  list<string>|Optional  $features  Feature enum values
     */
    public function __construct(
        #[Min(3), Max(255)]
        public string|Optional $title,

        #[Max(5000)]
        public string|null|Optional $description,

        public DealType|Optional $dealType,

        #[Min(1)]
        public int|Optional $priceCents,

        #[Max(100)]
        public string|Optional $city,

        #[Max(255)]
        public string|Optional $address,

        public PropertyType|Optional $propertyType,

        #[Between(0, 20)]
        public int|Optional $rooms,

        #[Between(0, 20)]
        public int|null|Optional $bathrooms,

        #[Between(5, 10000)]
        public float|Optional $area,

        #[Between(0, 200)]
        public int|null|Optional $floor,

        #[Between(1, 200)]
        public int|null|Optional $totalFloors,

        public bool|Optional $isPublished,

        public ListingBadge|null|Optional $badge,

        public bool|Optional $isNewBuild,

        /** Marks the deal as closed: the listing leaves the catalog, stays in the "Sold" tab. */
        public bool|Optional $isSold,

        #[Between(1800, 2100)]
        public int|null|Optional $yearBuilt,

        /** Feature enum values; replaces the whole list. */
        public array|Optional $features,
    ) {}

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'features.*' => ['distinct', Rule::enum(Feature::class)],
        ];
    }
}

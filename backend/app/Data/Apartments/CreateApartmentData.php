<?php

namespace App\Data\Apartments;

use App\Enums\DealType;
use App\Enums\Feature;
use App\Enums\ListingBadge;
use App\Enums\PropertyType;
use App\Models\ApartmentPhoto;
use App\Rules\Base64Image;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CreateApartmentData extends Data
{
    /**
     * @param  list<string>  $features  Feature enum values
     * @param  list<string>  $photos  base64 strings or data URIs
     */
    public function __construct(
        #[Min(3), Max(255)]
        public string $title,

        #[Max(5000)]
        public ?string $description,

        public DealType $dealType,

        #[Min(1)]
        public int $priceCents,

        #[Max(100)]
        public string $city,

        #[Max(255)]
        public string $address,

        // 0 = studio / commercial space without bedrooms
        #[Between(0, 20)]
        public int $rooms,

        #[Between(5, 10000)]
        public float $area,

        public PropertyType $propertyType = PropertyType::Apartment,

        #[Between(0, 20)]
        public ?int $bathrooms = null,

        #[Between(0, 200)]
        public ?int $floor = null,

        #[Between(1, 200)]
        public ?int $totalFloors = null,

        public bool $isPublished = true,

        public ?ListingBadge $badge = null,

        public bool $isNewBuild = false,

        #[Between(1800, 2100)]
        public ?int $yearBuilt = null,

        /** Feature enum values, e.g. ["balcony", "elevator"]. */
        public array $features = [],

        #[Max(ApartmentPhoto::MAX_PER_APARTMENT)]
        public array $photos = [],
    ) {}

    /**
     * Rules for every item of the photos array.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'photos.*' => ['string', new Base64Image],
            'features.*' => ['distinct', Rule::enum(Feature::class)],
        ];
    }
}

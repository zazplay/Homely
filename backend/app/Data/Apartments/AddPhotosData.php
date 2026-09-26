<?php

namespace App\Data\Apartments;

use App\Models\ApartmentPhoto;
use App\Rules\Base64Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class AddPhotosData extends Data
{
    /**
     * @param  list<string>  $photos  base64 strings or data URIs
     */
    public function __construct(
        #[Min(1), Max(ApartmentPhoto::MAX_PER_APARTMENT)]
        public array $photos,
    ) {}

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'photos.*' => ['string', new Base64Image],
        ];
    }
}

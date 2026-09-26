<?php

namespace App\Data\Apartments;

use App\Models\ApartmentPhoto;
use Spatie\LaravelData\Data;

class PhotoData extends Data
{
    public function __construct(
        public int $id,
        public string $url,
        public int $position,
    ) {}

    public static function fromModel(ApartmentPhoto $photo): self
    {
        return new self(
            id: $photo->id,
            url: $photo->url,
            position: $photo->position,
        );
    }
}

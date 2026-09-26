<?php

namespace App\Actions\Apartments;

use App\Models\ApartmentPhoto;
use App\Support\Media;

class DeleteApartmentPhoto
{
    public function handle(ApartmentPhoto $photo): void
    {
        $photo->delete();

        Media::disk()->delete($photo->path);
    }
}

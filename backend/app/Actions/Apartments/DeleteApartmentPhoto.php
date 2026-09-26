<?php

namespace App\Actions\Apartments;

use App\Models\ApartmentPhoto;
use Illuminate\Support\Facades\Storage;

class DeleteApartmentPhoto
{
    public function handle(ApartmentPhoto $photo): void
    {
        $photo->delete();

        Storage::disk(ApartmentPhoto::DISK)->delete($photo->path);
    }
}

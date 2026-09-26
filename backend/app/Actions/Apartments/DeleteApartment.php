<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;
use App\Models\ApartmentPhoto;
use Illuminate\Support\Facades\Storage;

class DeleteApartment
{
    public function handle(Apartment $apartment): void
    {
        $directory = "apartments/{$apartment->id}";

        // Photo rows go away via ON DELETE CASCADE, the files don't — remove them after the row is gone.
        $apartment->delete();

        Storage::disk(ApartmentPhoto::DISK)->deleteDirectory($directory);
    }
}

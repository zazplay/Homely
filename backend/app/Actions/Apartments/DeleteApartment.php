<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;
use App\Support\Media;

class DeleteApartment
{
    public function handle(Apartment $apartment): void
    {
        $directory = "apartments/{$apartment->id}";

        // Photo rows go away via ON DELETE CASCADE, the files don't — remove them after the row is gone.
        $apartment->delete();

        Media::disk()->deleteDirectory($directory);
    }
}

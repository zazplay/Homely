<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;
use App\Models\ApartmentPhoto;
use App\Support\Base64Image;
use App\Support\Media;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Decodes base64 photos, writes them to disk and stores only their paths in the DB.
 */
class StoreApartmentPhotos
{
    /**
     * @param  list<string>  $photos  base64 strings or data URIs (already validated)
     *
     * @throws ValidationException when the apartment would exceed the photo limit
     */
    public function handle(Apartment $apartment, array $photos): void
    {
        $existing = $apartment->photos()->count();

        if ($existing + count($photos) > ApartmentPhoto::MAX_PER_APARTMENT) {
            throw ValidationException::withMessages([
                'photos' => 'An apartment can have at most '.ApartmentPhoto::MAX_PER_APARTMENT." photos ({$existing} already uploaded).",
            ]);
        }

        $disk = Media::disk();
        $position = (int) $apartment->photos()->max('position');
        $written = [];

        try {
            foreach ($photos as $base64) {
                $image = Base64Image::fromString($base64);

                // Random file name: the client never controls the path on our disk.
                $path = "apartments/{$apartment->id}/".Str::uuid().'.'.$image->extension;
                $disk->put($path, $image->binary);
                $written[] = $path;

                $apartment->photos()->create([
                    'path' => $path,
                    'mime_type' => $image->mimeType,
                    'size_bytes' => $image->size(),
                    'position' => ++$position,
                ]);
            }
        } catch (Throwable $e) {
            // A DB rollback doesn't remove files, so clean up what was already written.
            $disk->delete($written);

            throw $e;
        }
    }
}

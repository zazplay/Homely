<?php

namespace App\Actions\Apartments;

use App\Data\Apartments\ApartmentData;
use App\Data\Apartments\CreateApartmentData;
use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateApartment
{
    public function __construct(private StoreApartmentPhotos $storePhotos) {}

    public function handle(User $realtor, CreateApartmentData $data): Apartment
    {
        $attributes = [
            ...$data->except('photos')->toArray(),
            'is_sold' => false,
        ];

        // Until the platform verifies a realtor's license, their listings are saved as drafts.
        if ($realtor->hasRole(UserRole::Realtor) && ! $realtor->agentProfile()->value('is_verified')) {
            $attributes['is_published'] = false;
        }

        // All-or-nothing: if any photo fails, the apartment row is rolled back too.
        return DB::transaction(function () use ($realtor, $attributes, $data) {
            // Created through the relation, so realtor_id always comes from the token.
            $apartment = $realtor->apartments()->create($attributes);

            $this->storePhotos->handle($apartment, $data->photos);

            return $apartment->load(ApartmentData::RELATIONS);
        });
    }
}

<?php

namespace App\Actions\Apartments;

use App\Data\Apartments\ApartmentData;
use App\Data\Apartments\UpdateApartmentData;
use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateApartment
{
    /**
     * @throws ValidationException
     */
    public function handle(Apartment $apartment, UpdateApartmentData $data, User $editor): Apartment
    {
        // Optional (not sent) fields are left out of toArray(), so only sent fields change.
        $attributes = $data->toArray();

        $publishing = ($attributes['is_published'] ?? false) === true;
        if ($publishing && $editor->hasRole(UserRole::Realtor) && ! $editor->agentProfile()->value('is_verified')) {
            throw ValidationException::withMessages([
                'is_published' => 'Your license is not verified yet — listings stay drafts until it is.',
            ]);
        }

        $apartment->update($attributes);

        return $apartment->load(ApartmentData::RELATIONS);
    }
}

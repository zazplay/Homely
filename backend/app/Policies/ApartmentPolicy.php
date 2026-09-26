<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\User;

class ApartmentPolicy
{
    /**
     * Admins can do anything; returning null falls through to the specific ability.
     */
    public function before(?User $user): ?bool
    {
        return $user?->hasRole(UserRole::Admin) ? true : null;
    }

    /**
     * Published listings are public; unpublished ones only for their realtor.
     */
    public function view(?User $user, Apartment $apartment): bool
    {
        return $apartment->is_published || ($user !== null && $this->owns($user, $apartment));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Realtor);
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $this->owns($user, $apartment);
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $this->owns($user, $apartment);
    }

    private function owns(User $user, Apartment $apartment): bool
    {
        return $apartment->realtor_id === $user->id;
    }
}

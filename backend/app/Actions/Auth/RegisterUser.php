<?php

namespace App\Actions\Auth;

use App\Data\Auth\RegisterData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function handle(RegisterData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User($data->only('name', 'email', 'password')->toArray());

            // "role" is not mass-assignable; it's set explicitly after RegisterData
            // has already restricted it to self-assignable roles (client / realtor).
            $user->role = $data->role;
            $user->save();

            if ($data->role === UserRole::Realtor) {
                // New realtors start unverified: their listings stay drafts until the license is checked.
                $user->agentProfile()->create([
                    'agency' => $data->agency,
                    'license_number' => $data->licenseNumber,
                    'specializations' => [],
                    'areas' => [],
                    'languages' => [],
                ]);
            }

            // Re-read so DB defaults are loaded — strict mode forbids missing attributes.
            return $user->refresh();
        });
    }
}

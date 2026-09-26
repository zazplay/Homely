<?php

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    /**
     * @throws ValidationException
     */
    public function handle(LoginData $data): User
    {
        $user = User::firstWhere('email', $data->email);

        // Same error for "no such email" and "wrong password" — don't reveal which emails exist.
        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }
}

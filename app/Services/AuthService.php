<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        // Role falls back to the DB default ("customer"); it is never taken from input.
        return User::create($data)->refresh();
    }

    /**
     * @throws ValidationException
     */
    public function login(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        // Same error for "no such email" and "wrong password" — don't reveal which emails exist.
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }

    public function issueToken(User $user, string $deviceName = 'api'): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }

    /**
     * Revokes only the token used in the current request; other devices stay logged in.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}

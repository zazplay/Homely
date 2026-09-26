<?php

namespace App\Data\Auth;

use App\Enums\UserRole;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class RegisterData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $name,

        #[Email, Max(255), Unique('users', 'email')]
        public string $email,

        #[Password(min: 8)]
        public string $password,

        // Admin can't be self-assigned.
        #[In(UserRole::Client->value, UserRole::Realtor->value)]
        public UserRole $role = UserRole::Client,

        /** Realtors only: goes to the agent profile. */
        #[Max(255)]
        public ?string $agency = null,

        /** Realtors only: checked by the platform before the profile gets "verified". */
        #[Max(30)]
        public ?string $licenseNumber = null,
    ) {}
}

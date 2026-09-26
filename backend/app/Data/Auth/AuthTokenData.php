<?php

namespace App\Data\Auth;

use App\Data\UserData;
use Spatie\LaravelData\Data;

class AuthTokenData extends Data
{
    public function __construct(
        public UserData $user,
        public string $token,
    ) {}
}

<?php

namespace App\Data;

use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The authenticated user (register / login / me). Password and tokens never get here.
 */
#[MapName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public UserRole $role,
        public ?string $avatarUrl,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role,
            avatarUrl: $user->avatar_url,
            createdAt: $user->created_at,
        );
    }
}

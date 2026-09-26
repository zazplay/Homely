<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Realtor = 'realtor';
    case Admin = 'admin';

    /**
     * Roles a user may pick during self-registration.
     *
     * @return list<self>
     */
    public static function selfAssignable(): array
    {
        return [self::Client, self::Realtor];
    }
}

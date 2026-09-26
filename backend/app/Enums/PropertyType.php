<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Townhouse = 'townhouse';
    case Loft = 'loft';
    case Commercial = 'commercial';

    /**
     * Home page categories group several property types.
     *
     * @return array<string, list<self>>
     */
    public static function categories(): array
    {
        return [
            'apartments' => [self::Apartment, self::Loft],
            'houses' => [self::House, self::Townhouse],
            'commercial' => [self::Commercial],
        ];
    }
}

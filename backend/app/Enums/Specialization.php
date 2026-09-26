<?php

namespace App\Enums;

enum Specialization: string
{
    case Buying = 'buying';
    case Selling = 'selling';
    case Rentals = 'rentals';
    case Luxury = 'luxury';
    case Commercial = 'commercial';
    case NewBuilds = 'new_builds';
}

<?php

namespace App\Enums;

/**
 * Amenities of a listing (search filters + "Features" block on the listing page).
 */
enum Feature: string
{
    case Balcony = 'balcony';
    case Parking = 'parking';
    case PetFriendly = 'pet_friendly';
    case Elevator = 'elevator';
    case Garden = 'garden';
    case Doorman = 'doorman';
    case InUnitLaundry = 'in_unit_laundry';
    case CentralAc = 'central_ac';
    case Dishwasher = 'dishwasher';
    case Gym = 'gym';
    case BikeStorage = 'bike_storage';
    case SouthFacing = 'south_facing';
}

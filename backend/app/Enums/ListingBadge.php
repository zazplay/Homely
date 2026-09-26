<?php

namespace App\Enums;

/**
 * Marketing label a realtor can put on a listing card.
 */
enum ListingBadge: string
{
    case New = 'new';
    case Hot = 'hot';
    case PriceDrop = 'price_drop';
}

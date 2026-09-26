<?php

namespace App\Data\Apartments;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Counters for the home page: hero badge and category cards.
 */
#[MapName(SnakeCaseMapper::class)]
class CatalogStatsData extends Data
{
    public function __construct(
        public int $total,
        public int $apartments,
        public int $houses,
        public int $commercial,
        public int $newBuilds,
    ) {}
}

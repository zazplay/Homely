<?php

namespace App\Actions\Apartments;

use App\Data\Apartments\CatalogStatsData;
use App\Enums\PropertyType;
use App\Models\Apartment;

/**
 * All home page counters in ONE query (conditional aggregation) instead of five COUNT queries.
 */
class GetCatalogStats
{
    public function handle(): CatalogStatsData
    {
        $select = [
            'COUNT(*) AS total',
            'SUM(CASE WHEN is_new_build THEN 1 ELSE 0 END) AS new_builds',
        ];
        $bindings = [];

        foreach (PropertyType::categories() as $category => $types) {
            $placeholders = implode(', ', array_fill(0, count($types), '?'));
            $select[] = "SUM(CASE WHEN property_type IN ({$placeholders}) THEN 1 ELSE 0 END) AS {$category}";
            array_push($bindings, ...array_map(fn (PropertyType $type) => $type->value, $types));
        }

        /** @var object{total: int|string, new_builds: int|string|null, apartments: int|string|null, houses: int|string|null, commercial: int|string|null} $row */
        $row = Apartment::query()->available()->selectRaw(implode(', ', $select), $bindings)->toBase()->first();

        // SUM() is NULL when there are no rows.
        return new CatalogStatsData(
            total: (int) $row->total,
            apartments: (int) $row->apartments,
            houses: (int) $row->houses,
            commercial: (int) $row->commercial,
            newBuilds: (int) $row->new_builds,
        );
    }
}

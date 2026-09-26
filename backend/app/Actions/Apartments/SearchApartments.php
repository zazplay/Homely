<?php

namespace App\Actions\Apartments;

use App\Data\Apartments\ApartmentData;
use App\Data\Apartments\ApartmentFilterData;
use App\Enums\Feature;
use App\Enums\PropertyType;
use App\Models\Apartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Public catalog search: filters + sorting + pagination.
 */
class SearchApartments
{
    /**
     * @return LengthAwarePaginator<int, Apartment>
     */
    public function handle(ApartmentFilterData $filters): LengthAwarePaginator
    {
        $types = $filters->propertyTypeList();
        $features = $filters->featureList();

        $query = Apartment::query()
            ->available()
            ->with(ApartmentData::RELATIONS) // eager load: a fixed number of queries instead of 1 + N
            ->when($filters->location, fn (Builder $q, string $location) => $q->where(
                fn (Builder $q) => $q->whereLike('city', "%{$location}%")->orWhereLike('address', "%{$location}%"),
            ))
            ->when($filters->city, fn (Builder $q, string $city) => $q->whereLike('city', $city))
            ->when($filters->dealType, fn (Builder $q, $type) => $q->where('deal_type', $type))
            ->when($filters->propertyType, fn (Builder $q, $type) => $q->where('property_type', $type))
            ->when($types !== [], fn (Builder $q) => $q->whereIn('property_type', array_map(fn (PropertyType $t) => $t->value, $types)))
            ->when($filters->category, fn (Builder $q, string $category) => $q->whereIn(
                'property_type',
                array_map(fn (PropertyType $t) => $t->value, PropertyType::categories()[$category]),
            ))
            // Every selected feature must be present in the JSON list.
            ->when($features !== [], fn (Builder $q) => $q->where(function (Builder $q) use ($features) {
                foreach ($features as $feature) {
                    $q->whereJsonContains('features', $feature->value);
                }
            }))
            ->when($filters->verified, fn (Builder $q) => $q->whereHas(
                'realtor.agentProfile',
                fn (Builder $q) => $q->where('is_verified', true),
            ))
            ->when($filters->newBuild, fn (Builder $q) => $q->where('is_new_build', true))
            ->when($filters->realtorId, fn (Builder $q, int $id) => $q->where('realtor_id', $id))
            ->when($filters->rooms !== null, fn (Builder $q) => $q->where('rooms', $filters->rooms))
            ->when($filters->roomsMin, fn (Builder $q, int $min) => $q->where('rooms', '>=', $min))
            ->when($filters->priceMin, fn (Builder $q, int $min) => $q->where('price_cents', '>=', $min))
            ->when($filters->priceMax, fn (Builder $q, int $max) => $q->where('price_cents', '<=', $max));

        match ($filters->sort) {
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            default => $query->latest(),
        };

        return $query
            ->orderByDesc('id') // stable order for equal prices / same-second timestamps
            ->paginate($filters->perPage);
    }
}

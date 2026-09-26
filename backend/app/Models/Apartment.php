<?php

namespace App\Models;

use App\Enums\DealType;
use App\Enums\Feature;
use App\Enums\ListingBadge;
use App\Enums\PropertyType;
use Carbon\CarbonImmutable;
use Database\Factories\ApartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $realtor_id
 * @property string $title
 * @property string|null $description
 * @property DealType $deal_type
 * @property PropertyType $property_type
 * @property int $price_cents
 * @property string $city
 * @property string $address
 * @property int $rooms
 * @property int|null $bathrooms
 * @property string $area
 * @property int|null $floor
 * @property int|null $total_floors
 * @property int|null $year_built
 * @property bool $is_published
 * @property bool $is_sold
 * @property ListingBadge|null $badge
 * @property bool $is_new_build
 * @property \Illuminate\Support\Collection<int, Feature>|null $features
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read User $realtor
 * @property-read Collection<int, ApartmentPhoto> $photos
 */
// realtor_id is not fillable: it always comes from the authenticated user.
#[Fillable([
    'title', 'description', 'deal_type', 'property_type', 'price_cents', 'city', 'address',
    'rooms', 'bathrooms', 'area', 'floor', 'total_floors', 'year_built', 'is_published', 'is_sold',
    'badge', 'is_new_build', 'features',
])]
class Apartment extends Model
{
    /** @use HasFactory<ApartmentFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'deal_type' => DealType::class,
            'property_type' => PropertyType::class,
            'price_cents' => 'integer',
            'rooms' => 'integer',
            'bathrooms' => 'integer',
            'area' => 'decimal:2',
            'floor' => 'integer',
            'total_floors' => 'integer',
            'is_published' => 'boolean',
            'badge' => ListingBadge::class,
            'is_new_build' => 'boolean',
            'is_sold' => 'boolean',
            'year_built' => 'integer',
            'features' => AsEnumCollection::of(Feature::class),
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function realtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realtor_id');
    }

    /**
     * @return HasMany<ApartmentPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ApartmentPhoto::class)->orderBy('position');
    }

    /**
     * Listings visible in the public catalog.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Published and still on the market — what the catalog shows.
     * Sold listings stay visible only on the realtor's profile.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_published', true)->where('is_sold', false);
    }
}

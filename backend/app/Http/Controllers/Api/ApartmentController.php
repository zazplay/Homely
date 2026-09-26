<?php

namespace App\Http\Controllers\Api;

use App\Actions\Apartments\CreateApartment;
use App\Actions\Apartments\DeleteApartment;
use App\Actions\Apartments\DeleteApartmentPhoto;
use App\Actions\Apartments\GetCatalogStats;
use App\Actions\Apartments\SearchApartments;
use App\Actions\Apartments\StoreApartmentPhotos;
use App\Actions\Apartments\UpdateApartment;
use App\Data\Apartments\AddPhotosData;
use App\Data\Apartments\ApartmentData;
use App\Data\Apartments\ApartmentFilterData;
use App\Data\Apartments\CatalogStatsData;
use App\Data\Apartments\CreateApartmentData;
use App\Data\Apartments\UpdateApartmentData;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentPhoto;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Spatie\LaravelData\PaginatedDataCollection;

class ApartmentController extends Controller
{
    /**
     * Public catalog: published listings that are still on the market.
     *
     * @return PaginatedDataCollection<array-key, ApartmentData>
     */
    #[QueryParameter('location', description: 'City, neighborhood or street (partial match)', type: 'string', example: 'Brooklyn')]
    #[QueryParameter('city', description: 'Exact city (case-insensitive)', type: 'string')]
    #[QueryParameter('deal_type', description: '`sale` or `rent`', type: 'string', example: 'sale')]
    #[QueryParameter('category', description: '`apartments`, `houses` or `commercial`', type: 'string')]
    #[QueryParameter('property_type', description: 'One of `apartment`, `house`, `townhouse`, `loft`, `commercial`', type: 'string')]
    #[QueryParameter('property_types', description: 'Comma-separated, any of them: `apartment,loft`', type: 'string')]
    #[QueryParameter('features', description: 'Comma-separated, all of them: `balcony,elevator`', type: 'string')]
    #[QueryParameter('verified', description: '1 = only license-verified agents', type: 'integer')]
    #[QueryParameter('new_build', description: '1 = only new builds', type: 'integer')]
    #[QueryParameter('realtor_id', description: 'Only listings of this realtor', type: 'integer')]
    #[QueryParameter('rooms', description: 'Exact number of bedrooms (0 = studio)', type: 'integer')]
    #[QueryParameter('rooms_min', description: 'At least N bedrooms', type: 'integer', example: 2)]
    #[QueryParameter('price_min', description: 'In cents', type: 'integer')]
    #[QueryParameter('price_max', description: 'In cents', type: 'integer')]
    #[QueryParameter('sort', description: '`newest` (default), `price_asc`, `price_desc`', type: 'string')]
    #[QueryParameter('per_page', description: '1–100, default 15', type: 'integer')]
    public function index(ApartmentFilterData $filters, SearchApartments $search): PaginatedDataCollection
    {
        return ApartmentData::collect($search->handle($filters), PaginatedDataCollection::class);
    }

    /**
     * Counters for the home page: total listings and per category.
     */
    public function stats(GetCatalogStats $getStats): CatalogStatsData
    {
        return $getStats->handle();
    }

    /**
     * The current realtor's own listings, drafts and sold included.
     *
     * @return PaginatedDataCollection<array-key, ApartmentData>
     */
    #[Middleware('auth:sanctum')]
    public function mine(Request $request): PaginatedDataCollection
    {
        $apartments = $request->user()->apartments()
            ->with(ApartmentData::RELATIONS)
            ->latest()
            ->orderByDesc('id')
            ->paginate(15);

        return ApartmentData::collect($apartments, PaginatedDataCollection::class);
    }

    #[Authorize('view', 'apartment')]
    public function show(Apartment $apartment): ApartmentData
    {
        return ApartmentData::fromModel($apartment->load(ApartmentData::RELATIONS));
    }

    /**
     * Create a listing. Photos are sent as base64 strings (or data URIs) and stored as files.
     * Listings of realtors whose license isn't verified yet are saved as drafts.
     */
    #[Middleware('auth:sanctum')]
    #[Authorize('create', Apartment::class)]
    #[BodyParameter('title', required: true, type: 'string', example: 'Sunny 3-bed with balcony')]
    #[BodyParameter('description', type: 'string', example: 'Renovated, south-facing, balcony.')]
    #[BodyParameter('deal_type', description: '`sale` or `rent`', required: true, type: 'string', example: 'sale')]
    #[BodyParameter('property_type', description: '`apartment` (default), `house`, `townhouse`, `loft`, `commercial`', type: 'string', example: 'apartment')]
    #[BodyParameter('price_cents', description: 'Price in cents; for rent — per month', required: true, type: 'integer', example: 78500000)]
    #[BodyParameter('city', required: true, type: 'string', example: 'Brooklyn')]
    #[BodyParameter('address', required: true, type: 'string', example: '214 7th Ave, Park Slope')]
    #[BodyParameter('rooms', description: 'Bedrooms; 0 = studio / commercial', required: true, type: 'integer', example: 3)]
    #[BodyParameter('bathrooms', type: 'integer', example: 2)]
    #[BodyParameter('area', description: 'Square meters', required: true, type: 'number', example: 131.9)]
    #[BodyParameter('floor', type: 'integer', example: 4)]
    #[BodyParameter('total_floors', type: 'integer', example: 6)]
    #[BodyParameter('year_built', type: 'integer', example: 1928)]
    #[BodyParameter('features', description: 'Feature values: `balcony`, `parking`, `pet_friendly`, `elevator`, `garden`, `doorman`, …', type: 'array<string>', example: ['balcony', 'elevator'])]
    #[BodyParameter('is_published', type: 'boolean', example: true)]
    #[BodyParameter('badge', description: '`new`, `hot`, `price_drop` or null', type: 'string')]
    #[BodyParameter('is_new_build', type: 'boolean', example: false)]
    #[BodyParameter('photos', description: 'Up to 20 base64 images (JPEG/PNG/WebP, 5 MB each)', type: 'array<string>', example: ['data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='])]
    public function store(CreateApartmentData $data, Request $request, CreateApartment $createApartment): ApartmentData
    {
        return ApartmentData::fromModel($createApartment->handle($request->user(), $data));
    }

    /**
     * Partial update: send only the fields you want to change. `is_sold: true` closes the deal.
     */
    #[Middleware('auth:sanctum')]
    #[Authorize('update', 'apartment')]
    #[BodyParameter('title', type: 'string')]
    #[BodyParameter('description', type: 'string')]
    #[BodyParameter('deal_type', type: 'string')]
    #[BodyParameter('property_type', type: 'string')]
    #[BodyParameter('price_cents', type: 'integer', example: 76000000)]
    #[BodyParameter('city', type: 'string')]
    #[BodyParameter('address', type: 'string')]
    #[BodyParameter('rooms', type: 'integer')]
    #[BodyParameter('bathrooms', type: 'integer')]
    #[BodyParameter('area', type: 'number')]
    #[BodyParameter('floor', type: 'integer')]
    #[BodyParameter('total_floors', type: 'integer')]
    #[BodyParameter('year_built', type: 'integer')]
    #[BodyParameter('features', type: 'array<string>')]
    #[BodyParameter('is_published', type: 'boolean')]
    #[BodyParameter('is_sold', type: 'boolean')]
    #[BodyParameter('badge', type: 'string')]
    #[BodyParameter('is_new_build', type: 'boolean')]
    public function update(UpdateApartmentData $data, Apartment $apartment, Request $request, UpdateApartment $updateApartment): ApartmentData
    {
        return ApartmentData::fromModel($updateApartment->handle($apartment, $data, $request->user()));
    }

    #[Middleware('auth:sanctum')]
    #[Authorize('delete', 'apartment')]
    public function destroy(Apartment $apartment, DeleteApartment $deleteApartment): Response
    {
        $deleteApartment->handle($apartment);

        return response()->noContent();
    }

    /**
     * Add photos to an existing listing.
     */
    #[Middleware('auth:sanctum')]
    #[Authorize('update', 'apartment')]
    #[BodyParameter('photos', description: 'Base64 images (JPEG/PNG/WebP, 5 MB each)', required: true, type: 'array<string>')]
    public function addPhotos(AddPhotosData $data, Apartment $apartment, StoreApartmentPhotos $storePhotos): ApartmentData
    {
        $storePhotos->handle($apartment, $data->photos);

        return ApartmentData::fromModel($apartment->load(ApartmentData::RELATIONS));
    }

    #[Middleware('auth:sanctum')]
    #[Authorize('update', 'apartment')]
    public function deletePhoto(Apartment $apartment, ApartmentPhoto $photo, DeleteApartmentPhoto $deletePhoto): Response
    {
        $deletePhoto->handle($photo);

        return response()->noContent();
    }
}

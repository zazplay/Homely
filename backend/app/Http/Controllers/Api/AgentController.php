<?php

namespace App\Http\Controllers\Api;

use App\Data\AgentData;
use App\Data\AgentFilterData;
use App\Data\AgentProfileData;
use App\Data\Apartments\ApartmentData;
use App\Enums\Language;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\DataCollection;

class AgentController extends Controller
{
    /**
     * Realtors with filters. Sorted by rating (default), deals or experience.
     *
     * @return DataCollection<array-key, AgentData>
     */
    #[QueryParameter('q', description: 'Name, agency or area', type: 'string', example: 'Greenleaf')]
    #[QueryParameter('specializations', description: 'Comma-separated, all of them: `buying,luxury`', type: 'string')]
    #[QueryParameter('areas', description: 'Comma-separated, any of them: `brooklyn,queens`', type: 'string')]
    #[QueryParameter('languages', description: 'Comma-separated, all of them: `spanish`', type: 'string')]
    #[QueryParameter('min_rating', type: 'number', example: 4.7)]
    #[QueryParameter('min_experience', description: 'Years', type: 'integer')]
    #[QueryParameter('sort', description: '`rating` (default), `deals`, `experience`', type: 'string')]
    #[QueryParameter('limit', description: '1–50, default 50', type: 'integer', example: 4)]
    public function index(AgentFilterData $filters): DataCollection
    {
        $specializations = $filters->specializationList();
        $areas = $filters->areaList();
        $languages = $filters->languageList();

        $agents = User::query()
            ->where('users.role', UserRole::Realtor)
            // JOIN instead of whereHas: we also sort by profile columns.
            ->join('agent_profiles', 'agent_profiles.user_id', '=', 'users.id')
            ->select('users.*')
            ->with('agentProfile')
            ->withCount([
                // only listings on the market (Apartment::available scope)
                'apartments as listings_count' => fn (Builder $q) => $q->scopes('available'),
            ])
            ->when($filters->q, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->whereLike('users.name', "%{$term}%")
                ->orWhereLike('agent_profiles.agency', "%{$term}%")
                // areas are stored as enum values in JSON ("new_jersey"); JSON has no LIKE in Postgres → cast to text
                ->orWhereRaw('LOWER(CAST(agent_profiles.areas AS TEXT)) LIKE ?', ['%'.str_replace(' ', '_', mb_strtolower($term)).'%'])))
            ->when($specializations !== [], fn (Builder $q) => $q->where(function (Builder $q) use ($specializations) {
                foreach ($specializations as $specialization) {
                    $q->whereJsonContains('agent_profiles.specializations', $specialization->value);
                }
            }))
            ->when($areas !== [], fn (Builder $q) => $q->where(function (Builder $q) use ($areas) {
                foreach ($areas as $area) {
                    $q->orWhereJsonContains('agent_profiles.areas', $area->value);
                }
            }))
            ->when($languages !== [], fn (Builder $q) => $q->where(function (Builder $q) use ($languages) {
                foreach ($languages as $language) {
                    $q->whereJsonContains('agent_profiles.languages', $language->value);
                }
            }))
            ->when($filters->minRating, fn (Builder $q, float $min) => $q->where('agent_profiles.rating', '>=', $min))
            ->when($filters->minExperience, fn (Builder $q, int $min) => $q->where('agent_profiles.experience_years', '>=', $min))
            // "IS NULL" first: Postgres puts NULLs on top for DESC, and new realtors have no rating yet.
            ->orderByRaw(match ($filters->sort) {
                'deals' => 'agent_profiles.deals_count DESC',
                'experience' => 'agent_profiles.experience_years DESC',
                default => 'agent_profiles.rating IS NULL, agent_profiles.rating DESC',
            })
            ->orderByDesc('agent_profiles.deals_count')
            ->orderBy('users.id')
            ->limit($filters->limit)
            ->get();

        return AgentData::collect($agents, DataCollection::class);
    }

    /**
     * Full public profile: stats, bio, reviews and listings (sold ones included).
     */
    public function show(User $agent): AgentProfileData
    {
        abort_unless($agent->hasRole(UserRole::Realtor), 404);

        $agent->load('agentProfile');

        $reviews = $agent->reviews()->latest()->limit(10)->get();
        $listings = $agent->apartments()
            ->published()
            ->with(ApartmentData::RELATIONS)
            ->latest()
            ->orderByDesc('id')
            ->get();

        return AgentProfileData::build($agent, $reviews, $listings);
    }

    /**
     * Allowed filter values — the frontend builds its chips from them.
     *
     * @return array<string, list<string>>
     */
    public function filters(): array
    {
        return [
            'specializations' => array_map(fn (Specialization $s) => $s->value, Specialization::cases()),
            'areas' => array_map(fn (ServiceArea $a) => $a->value, ServiceArea::cases()),
            'languages' => array_map(fn (Language $l) => $l->value, Language::cases()),
        ];
    }
}

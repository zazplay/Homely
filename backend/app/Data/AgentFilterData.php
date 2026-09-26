<?php

namespace App\Data;

use App\Enums\Language;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Rules\EnumList;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * ?q=greenleaf&specializations=buying,luxury&areas=brooklyn&languages=spanish&min_rating=4.7&min_experience=5&sort=deals
 */
#[MapName(SnakeCaseMapper::class)]
class AgentFilterData extends Data
{
    public function __construct(
        /** Name, agency or area. */
        #[Max(100)]
        public ?string $q = null,

        /** All of them must match. */
        public ?string $specializations = null,

        /** Any of them must match. */
        public ?string $areas = null,

        /** All of them must match. */
        public ?string $languages = null,

        #[Between(0, 5)]
        public ?float $minRating = null,

        #[Between(0, 60)]
        public ?int $minExperience = null,

        #[In('rating', 'deals', 'experience')]
        public string $sort = 'rating',

        #[Between(1, 50)]
        public int $limit = 50,
    ) {}

    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'specializations' => ['nullable', new EnumList(Specialization::class)],
            'areas' => ['nullable', new EnumList(ServiceArea::class)],
            'languages' => ['nullable', new EnumList(Language::class)],
        ];
    }

    /**
     * @return list<Specialization>
     */
    public function specializationList(): array
    {
        return EnumList::parse($this->specializations, Specialization::class);
    }

    /**
     * @return list<ServiceArea>
     */
    public function areaList(): array
    {
        return EnumList::parse($this->areas, ServiceArea::class);
    }

    /**
     * @return list<Language>
     */
    public function languageList(): array
    {
        return EnumList::parse($this->languages, Language::class);
    }
}

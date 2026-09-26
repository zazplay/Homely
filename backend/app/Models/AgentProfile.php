<?php

namespace App\Models;

use App\Enums\Language;
use App\Enums\ServiceArea;
use App\Enums\Specialization;
use App\Support\Media;
use Carbon\CarbonImmutable;
use Database\Factories\AgentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $title
 * @property string|null $agency
 * @property string|null $phone
 * @property string|null $bio
 * @property string|null $license_number
 * @property bool $is_verified
 * @property int $experience_years
 * @property string|null $rating
 * @property int $reviews_count
 * @property int $deals_count
 * @property int $sales_volume_cents
 * @property Collection<int, Specialization> $specializations
 * @property Collection<int, ServiceArea> $areas
 * @property Collection<int, Language> $languages
 * @property string|null $cover_path
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read string|null $cover_url
 * @property-read User $user
 */
#[Fillable([
    'title', 'agency', 'phone', 'bio', 'license_number', 'experience_years',
    'specializations', 'areas', 'languages',
])]
class AgentProfile extends Model
{
    /** @use HasFactory<AgentProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'experience_years' => 'integer',
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
            'deals_count' => 'integer',
            'sales_volume_cents' => 'integer',
            'specializations' => AsEnumCollection::of(Specialization::class),
            'areas' => AsEnumCollection::of(ServiceArea::class),
            'languages' => AsEnumCollection::of(Language::class),
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => Media::url($this->cover_path));
    }
}

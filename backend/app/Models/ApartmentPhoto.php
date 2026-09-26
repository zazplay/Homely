<?php

namespace App\Models;

use App\Support\Media;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $apartment_id
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read string $url
 */
#[Fillable(['path', 'mime_type', 'size_bytes', 'position'])]
class ApartmentPhoto extends Model
{
    public const MAX_PER_APARTMENT = 20;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Apartment, $this>
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Public URL of the file, e.g. http://localhost:8000/storage/apartments/1/abc.jpg
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => (string) Media::url($this->path));
    }
}

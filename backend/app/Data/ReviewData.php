<?php

namespace App\Data;

use App\Models\AgentReview;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ReviewData extends Data
{
    public function __construct(
        public int $id,
        public string $authorName,
        public ?string $dealLabel,
        public int $rating,
        public string $body,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(AgentReview $review): self
    {
        return new self(
            id: $review->id,
            authorName: $review->author_name,
            dealLabel: $review->deal_label,
            rating: $review->rating,
            body: $review->body,
            createdAt: $review->created_at,
        );
    }
}

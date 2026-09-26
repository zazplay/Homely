<?php

namespace App\Data\Inquiries;

use App\Models\Inquiry;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A lead in the realtor's inbox.
 */
#[MapName(SnakeCaseMapper::class)]
class InquiryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $contact,
        public string $message,
        public ?int $apartmentId,
        public ?string $apartmentTitle,
        public ?CarbonImmutable $readAt,
        public CarbonImmutable $createdAt,
    ) {}

    /**
     * Expects "apartment" to be eager-loaded.
     */
    public static function fromModel(Inquiry $inquiry): self
    {
        return new self(
            id: $inquiry->id,
            name: $inquiry->name,
            contact: $inquiry->contact,
            message: $inquiry->message,
            apartmentId: $inquiry->apartment_id,
            apartmentTitle: $inquiry->apartment?->title,
            readAt: $inquiry->read_at,
            createdAt: $inquiry->created_at,
        );
    }
}

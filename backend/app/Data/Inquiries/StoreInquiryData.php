<?php

namespace App\Data\Inquiries;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * "Contact agent" form: from a listing page (apartment_id) or an agent profile (agent_id).
 */
#[MapName(SnakeCaseMapper::class)]
class StoreInquiryData extends Data
{
    public function __construct(
        #[Min(2), Max(100)]
        public string $name,

        /** Phone or email. */
        #[Min(5), Max(150)]
        public string $contact,

        #[Min(5), Max(2000)]
        public string $message,

        #[Exists('apartments', 'id')]
        public ?int $apartmentId = null,

        #[Exists('users', 'id')]
        public ?int $agentId = null,
    ) {}

    /**
     * Written as plain rules: the RequiredWithout attribute doesn't resolve snake_case
     * field names correctly together with MapName.
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'apartment_id' => ['required_without:agent_id'],
            'agent_id' => ['required_without:apartment_id'],
        ];
    }
}

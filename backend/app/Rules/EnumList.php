<?php

namespace App\Rules;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a comma-separated list of enum values from the query string,
 * e.g. ?features=balcony,parking
 */
class EnumList implements ValidationRule
{
    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public function __construct(private string $enum) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a comma-separated list.');

            return;
        }

        $invalid = array_filter(self::split($value), fn (string $item) => $this->enum::tryFrom($item) === null);

        if ($invalid !== []) {
            $fail('The :attribute contains unknown values: '.implode(', ', $invalid).'.');
        }
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return list<T>
     */
    public static function parse(?string $value, string $enum): array
    {
        return array_values(array_filter(array_map(fn (string $item) => $enum::tryFrom($item), self::split($value ?? ''))));
    }

    /**
     * @return list<string>
     */
    private static function split(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $item) => $item !== ''));
    }
}

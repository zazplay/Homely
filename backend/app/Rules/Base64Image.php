<?php

namespace App\Rules;

use App\Support\Base64Image as Image;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

/**
 * Validates that the value is a base64 (or data URI) JPEG / PNG / WebP image up to 5 MB.
 */
class Base64Image implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a base64 string.');

            return;
        }

        try {
            Image::fromString($value);
        } catch (InvalidArgumentException $e) {
            $fail('The :attribute '.$e->getMessage());
        }
    }
}

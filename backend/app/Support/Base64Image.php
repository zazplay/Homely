<?php

namespace App\Support;

use finfo;
use InvalidArgumentException;

/**
 * A decoded, verified base64 image.
 *
 * Accepts both a raw base64 string and a data URI ("data:image/png;base64,....").
 * The real type is detected from the file bytes — the data URI prefix is never trusted.
 */
final readonly class Base64Image
{
    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB after decoding

    /** Allowed MIME type => file extension. */
    public const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private function __construct(
        public string $binary,
        public string $mimeType,
        public string $extension,
    ) {}

    /**
     * @throws InvalidArgumentException with a human-readable reason
     */
    public static function fromString(string $input): self
    {
        // Strip an optional "data:<mime>;base64," prefix.
        $payload = preg_replace('/^data:[\w.+\/-]+;base64,/', '', trim($input)) ?? '';

        // strict: fail on any character outside the base64 alphabet instead of silently skipping it.
        $binary = base64_decode($payload, strict: true);

        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('is not valid base64.');
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw new InvalidArgumentException('must not be larger than 5 MB.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: '';

        if (! array_key_exists($mimeType, self::ALLOWED)) {
            throw new InvalidArgumentException('must be a JPEG, PNG or WebP image.');
        }

        return new self($binary, $mimeType, self::ALLOWED[$mimeType]);
    }

    public function size(): int
    {
        return strlen($this->binary);
    }
}

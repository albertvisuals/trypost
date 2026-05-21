<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves workspace brand hex colours into human-readable names for
 * image-generation prompts. Models follow named colours more reliably
 * than raw hex codes.
 */
final readonly class BrandImagePalette
{
    public ?string $brandColorName;

    public ?string $backgroundColorName;

    public ?string $textColorName;

    public function __construct(
        ?string $brandColor = null,
        ?string $backgroundColor = null,
        ?string $textColor = null,
    ) {
        $this->brandColorName = self::resolveName($brandColor);
        $this->backgroundColorName = self::resolveName($backgroundColor);
        $this->textColorName = self::resolveName($textColor);
    }

    public function isDefined(): bool
    {
        return $this->brandColorName !== null
            || $this->backgroundColorName !== null
            || $this->textColorName !== null;
    }

    private static function resolveName(?string $hex): ?string
    {
        if ($hex === null || trim($hex) === '') {
            return null;
        }

        return HexColorName::approximate($hex);
    }
}

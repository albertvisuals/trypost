<?php

declare(strict_types=1);

use App\Support\BrandImagePalette;

test('isDefined is false when all colours are missing', function () {
    $palette = new BrandImagePalette;

    expect($palette->isDefined())->toBeFalse();
});

test('resolves all three workspace colours to approximate names', function () {
    $palette = new BrandImagePalette(
        brandColor: '#facc15',
        backgroundColor: '#ffffff',
        textColor: '#0f172a',
    );

    expect($palette->isDefined())->toBeTrue()
        ->and($palette->brandColorName)->toBe('golden yellow')
        ->and($palette->backgroundColorName)->toBe('off-white')
        ->and($palette->textColorName)->toContain('blue');
});

test('ignores malformed hex values', function () {
    $palette = new BrandImagePalette(
        brandColor: 'not-a-hex',
        backgroundColor: '#ffffff',
        textColor: '',
    );

    expect($palette->brandColorName)->toBeNull()
        ->and($palette->backgroundColorName)->toBe('off-white')
        ->and($palette->textColorName)->toBeNull()
        ->and($palette->isDefined())->toBeTrue();
});

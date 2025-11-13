<?php

declare(strict_types=1);

use App\Contracts\HasEnumFeatures;
use Lorisleiva\Actions\Concerns\AsAction;

arch()->preset()->php(); // @phpstan-ignore-line
arch()->preset()->security(); // @phpstan-ignore-line
arch()->preset()->laravel(); // @phpstan-ignore-line

test('all files in App\\Enums should be enums', function (): void {
    expect('App\\Enums')
        ->toBeEnums();
});

test('all enums should use HasEnumFeatures trait', function (): void {
    expect('App\\Enums')
        ->enums()
        ->toUseTrait(HasEnumFeatures::class);
});

test('all enums should follow Spatie naming convention without Enum suffix', function (): void {
    expect('App\\Enums')
        ->not->toHaveSuffix('Enum');
});

test('all actions use laravel action traits', function (): void {
    expect('App\Actions')
        ->classes()
        ->toUseTrait(AsAction::class);
});

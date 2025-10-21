<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Override;
use UnknowSk\FilamentLogger\Resources\ActivityResource as BaseActivityResource;

final class ActivityResource extends BaseActivityResource
{
    #[Override]
    public static function getNavigationGroup(): string
    {
        return __('navigation.user_management');
    }

    #[Override]
    public static function getNavigationSort(): int
    {
        return 10;
    }
}

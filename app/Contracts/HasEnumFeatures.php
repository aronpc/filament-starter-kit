<?php

declare(strict_types=1);

namespace App\Contracts;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

/**
 * @phpstan-ignore trait.unused
 */
trait HasEnumFeatures
{
    use Comparable;
    use From;
    use InvokableCases;
    use Metadata;
    use Names;
    use Options;
    use Values;
}

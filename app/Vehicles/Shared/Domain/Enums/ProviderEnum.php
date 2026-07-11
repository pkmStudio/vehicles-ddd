<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums;

use App\Vehicles\Shared\Domain\Traits\EnumHelperTrait;

enum ProviderEnum: string
{
    use EnumHelperTrait;

    // TecDoc Database — данные из внешнего справочника TecDoc
    case TD = 'TD';

    // Our Database — свои записи (не из TecDoc)
    case OD = 'OD';
}

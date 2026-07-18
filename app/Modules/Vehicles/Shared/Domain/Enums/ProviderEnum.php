<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Enums;

enum ProviderEnum: string
{
    // TecDoc Database — данные из внешнего справочника TecDoc
    case TD = 'TD';

    // Our Database — свои записи (не из TecDoc)
    case OD = 'OD';
}

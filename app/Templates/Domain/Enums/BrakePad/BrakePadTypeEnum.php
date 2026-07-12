<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\BrakePad;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Конструктив тормозной системы, под которую сделаны колодки. */
enum BrakePadTypeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case DISK = 'Дисковые';
    case DRUM = 'Барабанные';
}

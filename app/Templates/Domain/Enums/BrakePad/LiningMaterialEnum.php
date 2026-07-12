<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\BrakePad;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/**
 * Материал накладок тормозных колодок. Сейчас всего один кейс — справочник в dan-center ещё не
 * заполнен полностью, добавлять новые кейсы по мере появления реальных значений.
 */
enum LiningMaterialEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case ASBESTOS_FREE = 'Безасбестовые';
}

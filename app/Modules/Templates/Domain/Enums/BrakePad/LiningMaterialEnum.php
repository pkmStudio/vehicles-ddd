<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\BrakePad;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/**
 * Материал накладок тормозных колодок. Сейчас всего один кейс — справочник в dan-center ещё не
 * заполнен полностью, добавлять новые кейсы по мере появления реальных значений.
 */
enum LiningMaterialEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case ASBESTOS_FREE = 'Безасбестовые';
}

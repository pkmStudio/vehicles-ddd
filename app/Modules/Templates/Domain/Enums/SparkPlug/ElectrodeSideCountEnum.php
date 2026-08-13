<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\SparkPlug;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/**
 * Количество боковых электродов свечи. String-backed (не int), чтобы работать с общим
 * `EnumHelperTrait` (`fromLabel`/`fromName` ожидают строковый `->value`).
 */
enum ElectrodeSideCountEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case ONE = '1';
    case TWO = '2';
    case THREE = '3';
    case FOUR = '4';
}

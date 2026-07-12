<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\SparkPlug;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

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

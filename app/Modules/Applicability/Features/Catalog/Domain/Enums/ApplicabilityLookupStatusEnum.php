<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\Enums;

/**
 * Описывает внутренний результат проверки применяемости для HTTP adapter-а каталога.
 */
enum ApplicabilityLookupStatusEnum: string
{
    case COMPATIBLE = 'compatible';
    case UNKNOWN = 'unknown';
    case NOMENCLATURE_NOT_FOUND = 'nomenclature_not_found';
    case MODIFICATION_NOT_FOUND = 'modification_not_found';
}

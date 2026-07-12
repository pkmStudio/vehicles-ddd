<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums;

/**
 * Шаблон заполнения details у PartSpecification.
 * Значение — слаг шаблона; сборка details из строки для конкретного шаблона — забота
 * `Templates\Application\Factories\DetailsDataFactory::buildFromRow()` (порт —
 * `Domain\Contracts\Factories\DetailsDataFactoryInterface`), не самого enum'а — это только
 * декларация словаря шаблонов, без поведения. Заменяет прежнюю таблицу detail_templates + types.
 */
enum DetailTemplateEnum: string
{
    case WIPER = 'wiper';
    case SPARK_PLUGS = 'sparkPlugs';
    case OIL_FILTER = 'oilFilter';
    case AIR_FILTER = 'airFilter';
}

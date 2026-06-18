<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

use Dan\FieldTemplates\AbstractTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\AirFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\OilFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\SparkPlugTemplate;
use App\Vehicles\Domain\Templates\Vehicle\Templates\WiperTemplate;

/**
 * Шаблон заполнения details у PartSpecification.
 * Значение — слаг шаблона; класс-шаблон (поля для заполнения) резолвится в коде.
 * Заменяет прежнюю таблицу detail_templates + types (см. RESEARCH.md).
 */
enum DetailTemplateEnum: string
{
    case WIPER = 'wiper';
    case SPARK_PLUGS = 'sparkPlugs';
    case OIL_FILTER = 'oilFilter';
    case AIR_FILTER = 'airFilter';

    /**
     * Класс-шаблон, описывающий поля для заполнения details.
     */
    public function template(): AbstractTemplate
    {
        return match ($this) {
            self::WIPER => app(WiperTemplate::class),
            self::SPARK_PLUGS => app(SparkPlugTemplate::class),
            self::OIL_FILTER => app(OilFilterTemplate::class),
            self::AIR_FILTER => app(AirFilterTemplate::class),
        };
    }
}

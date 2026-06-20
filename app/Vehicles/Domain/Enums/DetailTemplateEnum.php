<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

use App\Vehicles\Domain\Templates\Engine\Templates\AirFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\OilFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\SparkPlugTemplate;
use App\Vehicles\Domain\Templates\Vehicle\Templates\WiperTemplate;
use Dan\FieldTemplates\AbstractTemplate;

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
     * FQCN класса-шаблона, описывающего поля для заполнения details.
     * Инстанс резолвится в Application (через контейнер/реестр), не в Domain —
     * enum остаётся чистым, без Service Locator.
     *
     * @return class-string<AbstractTemplate>
     */
    public function templateClass(): string
    {
        return match ($this) {
            self::WIPER => WiperTemplate::class,
            self::SPARK_PLUGS => SparkPlugTemplate::class,
            self::OIL_FILTER => OilFilterTemplate::class,
            self::AIR_FILTER => AirFilterTemplate::class,
        };
    }
}

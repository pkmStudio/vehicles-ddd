<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Application\Traits\ParsesBooleanCells;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CabinFilterDetailsData;

/**
 * Строит форму шаблона `cabinFilter` (Nomenclature) из Excel-строки — структурно идентична
 * `AirFilterDetailsBuilder`. Не подключена ни к одному Import/Export сценарию — см. докблок
 * `CabinFilterDetailsData`. Простой класс без собственного порта.
 */
final readonly class CabinFilterDetailsBuilder
{
    use BuildsNomenclatureMetrics;
    use ParsesBooleanCells;

    /**
     * Этот метод собирает nomenclature cabin-filter details из Excel-строки.
     * Шаги:
     * 1) Читает исполнение и форму фильтра через enum-справочники.
     * 2) Читает признак корпуса как обязательный boolean label.
     * 3) Читает вид фильтра и общий блок габаритов.
     * 4) Возвращает `CabinFilterDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): CabinFilterDetailsData
    {
        return new CabinFilterDetailsData(
            performance: $cursor->pullRequiredLabel(PerformanceEnum::class, 'Исполнение фильтра')->name,
            form: $cursor->pullRequiredLabel(FormEnum::class, 'Форма фильтра')->name,
            frame: $this->pullRequiredBoolLabel($cursor, 'Корпус'),
            filterType: $cursor->pullRequiredLabel(FilterMediaTypeEnum::class, 'Вид фильтра')->name,
            metrics: $this->buildMetrics($cursor),
        );
    }
}

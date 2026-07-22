<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;

/**
 * Формирует базовые Excel-ячейки двигателя без шаблонных details.
 */
final readonly class EngineExportRow implements EngineExportRowInterface
{
    /**
     * Возвращает базовые заголовки двигателя.
     */
    public function getBaseHeadings(): array
    {
        return [
            'ID двигателя TecDoc',
            'Код двигателя',
            'Объём (куб. см)',
            'Тип топлива',
            'Мощность л.с. от',
            'Мощность л.с. до',
            'Кол-во цилиндров',
            'Диаметр цилиндров',
            'Кол-во клапанов',
        ];
    }

    /**
     * Возвращает базовые ячейки двигателя.
     */
    public function getBaseData(EngineData $engine): array
    {
        return [
            $engine->engId,
            $engine->codeEngine,
            $engine->engineCapacity,
            $engine->engFuelType?->value,
            $engine->engPowerPsStart,
            $engine->engPowerPsUpto,
            $engine->cylinderCount,
            $engine->cylinderDiameter,
            $engine->engNumberOfValves,
        ];
    }
}

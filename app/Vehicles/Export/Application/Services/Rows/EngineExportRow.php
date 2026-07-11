<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\Rows;

use App\Vehicles\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Export\Domain\ModelData\Engine\EngineData;

final readonly class EngineExportRow implements EngineExportRowInterface
{
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

    public function getBaseData(EngineData $engine): array
    {
        return [
            $engine->engId,
            $engine->codeEngine,
            $engine->engineCapacity,
            $engine->engFuelType,
            $engine->engPowerPsStart,
            $engine->engPowerPsUpto,
            $engine->cylinderCount,
            $engine->cylinderDiameter,
            $engine->engNumberOfValves,
        ];
    }
}

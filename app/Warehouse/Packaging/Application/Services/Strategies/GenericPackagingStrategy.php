<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Расчёт упаковки для типов без своей специализированной стратегии (10 "прочих" типов, у которых
 * `details.metrics` заполнены, и `generic`/V_BELT, у которого `details` всегда пустой). Если ни у
 * одной номенклатуры нет заполненных `details.metrics` — использует условные размеры небольшой
 * коробки (DEFAULT_*), чтобы Kit можно было создать без падения; иначе — реальные максимумы.
 */
final readonly class GenericPackagingStrategy extends AbstractPackagingStrategy
{
    private const int DEFAULT_WEIGHT = 5;

    private const float DEFAULT_WIDTH = 100;

    private const float DEFAULT_HEIGHT = 50;

    private const float DEFAULT_LENGTH = 150;

    /**
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $metrics = $this->resolveMetrics($nomenclatures);

        $dto = new PackagingBoxRequirementDTO(
            weight: self::DEFAULT_WEIGHT,
            width: $metrics['width'] ?? self::DEFAULT_WIDTH,
            height: $metrics['height'] ?? self::DEFAULT_HEIGHT,
            length: $metrics['length'] ?? self::DEFAULT_LENGTH,
        );

        return $this->calculatePak($type, $type->name, $dto, $packDimensions);
    }

    /**
     * Этот метод вычисляет максимальные габариты по `details.metrics` всех номенклатур.
     * Шаги:
     * 1) Пропустить номенклатуры без заполненного `details.metrics` (шаблон типа его не собирает).
     * 2) Взять максимум по каждому измерению среди найденных.
     * 3) Если ни у одной номенклатуры метрик нет — вернуть null (вызывающий подставит дефолты).
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @return array{length: float, width: float, height: float}|null
     */
    private function resolveMetrics(array $nomenclatures): ?array
    {
        $maxLength = $maxWidth = $maxHeight = 0.0;
        $found = false;

        foreach ($nomenclatures as $nomenclature) {
            $metrics = $nomenclature->details['metrics'] ?? null;
            if (! $metrics) {
                continue;
            }

            $found = true;
            $maxLength = max($maxLength, $this->getMaxValue($metrics['length'] ?? [0]));
            $maxWidth = max($maxWidth, $this->getMaxValue($metrics['width'] ?? [0]));
            $maxHeight = max($maxHeight, $this->getMaxValue($metrics['height'] ?? [0]));
        }

        if (! $found) {
            return null;
        }

        return ['length' => $maxLength, 'width' => $maxWidth, 'height' => $maxHeight];
    }
}

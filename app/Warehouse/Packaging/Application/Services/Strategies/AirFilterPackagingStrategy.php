<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для воздушных фильтров. Список исторических артикулов ищет коробку строго по
 * габаритам без запаса на зазор; для всех остальных — стандартный расчёт с увеличенным (15мм,
 * не 5мм) зазором, т.к. воздушные фильтры этой категории обычно крупнее заявленных габаритов.
 */
final readonly class AirFilterPackagingStrategy extends AbstractPackagingStrategy
{
    private const array PREDEFINED_PART_NUMBERS = [
        'LA-1019', 'LA-1925', 'LA-1446', 'LA-827', 'LA-1354',
        'LA-1001', 'LA-2076', 'LA-1508', 'LA-1935', 'LA-228-1',
    ];

    /**
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $maxLength = $maxHeight = $maxWidth = 0.0;

        foreach ($nomenclatures as $nomenclature) {
            $metrics = $nomenclature->details['metrics'] ?? [];
            $maxLength = max($maxLength, $this->getMaxValue($metrics['length'] ?? [0]));
            $maxHeight = max($maxHeight, $this->getMaxValue($metrics['height'] ?? [0]));
            $maxWidth = max($maxWidth, $this->getMaxValue($metrics['width'] ?? [0]));
        }

        $dto = new PackagingBoxRequirementDTO(
            weight: 70,
            width: $maxWidth,
            height: $maxHeight,
            length: $maxLength,
        );

        foreach ($nomenclatures as $nomenclature) {
            if (! in_array($nomenclature->partNumber, self::PREDEFINED_PART_NUMBERS, true)) {
                continue;
            }

            $itemDimensions = [$dto->length, $dto->width, $dto->height];

            $suitableBox = $packDimensions
                ->sortBy(fn (PackDimensionData $box): int => $box->length * $box->width * $box->height)
                ->first(fn (PackDimensionData $box): bool => $this->canFit(
                    item: $itemDimensions,
                    box: [$box->length, $box->width, $box->height],
                ));

            return $suitableBox ?? $this->calculatePak($type, 'воздушных фильтров', $dto, $packDimensions);
        }

        return $this->calculatePak($type, 'воздушных фильтров', $dto, $packDimensions, oversizeLimit: 15);
    }
}

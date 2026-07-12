<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для салонных фильтров. Один артикул (`LAC-513C`) исторически всегда уходит в
 * первую доступную коробку без проверки габаритов — унаследованное из dan-center бизнес-исключение.
 */
final readonly class CabinFilterPackagingStrategy extends AbstractPackagingStrategy
{
    private const string EXACT_PART_NUMBER = 'LAC-513C';

    /**
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $maxLength = $maxHeight = $maxWidth = 0.0;

        foreach ($nomenclatures as $nomenclature) {
            if ($nomenclature->partNumber === self::EXACT_PART_NUMBER) {
                return $packDimensions->first();
            }

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

        return $this->calculatePak($type, 'салонных фильтров', $dto, $packDimensions);
    }
}

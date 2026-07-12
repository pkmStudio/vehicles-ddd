<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Warehouse\Packaging\Domain\DTOs\PackagingBoxRequirementDTO;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Подбирает упаковку для тормозных колодок. Сперва пробует точное совпадение по артикулу (в
 * `pack_dimensions.name` для части товаров хранится сам артикул подходящей готовой коробки — так
 * унаследовано из dan-center), иначе — стандартный габаритный расчёт, где ширина умножается на
 * `quantity_in_pak` (колодки кладутся в упаковке в ряд по ширине).
 */
final readonly class BrakePadsPackagingStrategy extends AbstractPackagingStrategy implements PackagingStrategyInterface
{
    /**
     * Проверяет, что стратегия применима к detail-шаблону тормозных колодок.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::BRAKE_PADS;
    }

    /**
     * Этот метод возвращает упаковку для набора колодок.
     *
     * Шаги:
     * 1) Попробовать найти точное совпадение коробки по артикулу (только для одиночной номенклатуры).
     * 2) Если не найдено — посчитать максимальные габариты по `details.metrics`, умножив суммарную
     *    ширину на количество колодок в упаковке.
     * 3) Подобрать/создать коробку через `calculatePackDimension()`.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $matchedByPartNumber = $this->resolveByPartNumber($nomenclatures, $packDimensions);
        if ($matchedByPartNumber !== null) {
            return $matchedByPartNumber;
        }

        $maxLength = $maxHeight = $maxWidth = 0.0;
        $quantityInPak = 0;

        foreach ($nomenclatures as $nomenclature) {
            $metrics = $nomenclature->details['metrics'] ?? [];

            $quantityInPak += $nomenclature->quantityInPak;
            $maxLength = max($maxLength, $this->getMaxValue($metrics['length'] ?? [0]));
            $maxHeight = max($maxHeight, $this->getMaxValue($metrics['height'] ?? [0]));
            $maxWidth = max($maxWidth, $this->getMaxValue($metrics['width'] ?? [0]));
        }

        $dto = new PackagingBoxRequirementDTO(
            weight: 5,
            width: $quantityInPak * $maxWidth,
            height: $maxHeight,
            length: $maxLength,
        );

        return $this->calculatePackDimension(
            type: $type,
            name: 'колодок',
            dto: $dto,
            packDimensions: $packDimensions,
        );
    }

    /**
     * Этот метод ищет коробку по артикулу для одиночной номенклатуры.
     *
     * Шаги:
     * 1) Проверить, что в комплекте ровно одна номенклатура — иначе точное совпадение не применимо.
     * 2) Нормализовать артикул номенклатуры и имя коробки через trim + lowercase.
     * 3) Вернуть найденную коробку или null, если совпадения нет.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    private function resolveByPartNumber(array $nomenclatures, Collection $packDimensions): ?PackDimensionData
    {
        if (count($nomenclatures) !== 1) {
            Log::warning('BrakePadsPackagingStrategy: fallback to dimensions for multi-nomenclature kit', [
                'nomenclatures_count' => count($nomenclatures),
            ]);

            return null;
        }

        $partNumber = mb_strtolower(trim($nomenclatures[array_key_first($nomenclatures)]->partNumber));

        if ($partNumber === '') {
            Log::warning('BrakePadsPackagingStrategy: fallback to dimensions because part number is empty');

            return null;
        }

        $matched = $packDimensions->first(
            static fn (PackDimensionData $box): bool => mb_strtolower(trim($box->name)) === $partNumber,
        );

        if ($matched === null) {
            Log::warning('BrakePadsPackagingStrategy: fallback to dimensions because box not found by part number', [
                'part_number' => $partNumber,
            ]);
        }

        return $matched;
    }
}

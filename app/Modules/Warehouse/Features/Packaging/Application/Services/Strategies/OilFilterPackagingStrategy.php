<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для масляных фильтров строго по габаритам первой номенклатуры комплекта.
 * Единственная стратегия, которая НЕ создаёт новую коробку при отсутствии подходящей — бросает
 * `PackDimensionNotResolvableException` (вызывающий, `KitProperties`, ловит именно этот тип).
 */
final readonly class OilFilterPackagingStrategy extends AbstractPackagingStrategy implements PackagingStrategyInterface
{
    /**
     * Проверяет, что стратегия применима к detail-шаблону масляных фильтров.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::OIL_FILTER;
    }

    /**
     * Этот метод возвращает подходящую существующую упаковку масляного фильтра.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     *
     * @throws PackDimensionNotResolvableException
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $item = $nomenclatures[array_key_first($nomenclatures)];
        $metrics = $item->details['metrics'] ?? [];

        $itemDimensions = [
            $this->getMaxValue($metrics['length'] ?? [0]),
            $this->getMaxValue($metrics['width'] ?? [0]),
            $this->getMaxValue($metrics['height'] ?? [0]),
        ];

        $suitableBox = $packDimensions
            ->sortBy(fn (PackDimensionData $box): int => $box->length * $box->width * $box->height)
            ->first(fn (PackDimensionData $box): bool => $this->canFit(
                item: $itemDimensions,
                box: [$box->length, $box->width, $box->height],
            ));

        if ($suitableBox === null) {
            throw new PackDimensionNotResolvableException(sprintf(
                'Не найдена подходящая упаковка для фильтра %s (%s×%s×%s мм)',
                $item->id,
                $itemDimensions[0],
                $itemDimensions[1],
                $itemDimensions[2],
            ));
        }

        return $suitableBox;
    }
}

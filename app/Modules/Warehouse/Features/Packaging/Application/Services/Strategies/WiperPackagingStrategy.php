<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для щёток стеклоочистителя по длине щётки — единственная стратегия, которая
 * никогда не создаёт новую коробку (только выбирает из существующих), поэтому не наследует
 * `AbstractPackagingStrategy` (её `calculatePackDimension()`/`Command` здесь не нужны).
 */
final readonly class WiperPackagingStrategy implements PackagingStrategyInterface
{
    /**
     * Проверяет, что стратегия применима к detail-шаблону щёток стеклоочистителя.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::WIPER;
    }

    /**
     * Этот метод возвращает коробку, длины которой хватает на самую длинную щётку в комплекте.
     *
     * Шаги:
     * 1) Определить максимальную длину среди `length_main`/`length_second` всех номенклатур.
     * 2) Отсортировать коробки по длине по возрастанию и взять первую, которая не короче.
     * 3) Если такой нет — взять самую длинную из существующих (fallback, не создаём новую).
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $maxLength = 0;

        foreach ($nomenclatures as $nomenclature) {
            $lengthMain = (int) ($nomenclature->details['length_main'] ?? 0);
            $lengthSecond = (int) ($nomenclature->details['length_second'] ?? 0);

            $maxLength = max($maxLength, $lengthMain, $lengthSecond);
        }

        $boxLongEnough = fn (PackDimensionData $box): bool => $box->length >= $maxLength;

        $suitableBox = $packDimensions
            ->sortBy('length')
            ->first($boxLongEnough);

        return $suitableBox ?? $packDimensions->sortByDesc('length')->first();
    }
}

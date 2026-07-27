<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies;

use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Комплект из номенклатур одного типа. Fallback-стратегия — регистрируется последней в
 * `KitPropertiesServiceProvider`, подходит для любого набора с единственным `typeId`.
 */
final readonly class SingleTypeStrategy implements KitCompositionStrategyInterface
{
    /**
     * Проверяет, что все номенклатуры набора имеют один typeId.
     */
    public function supports(Collection $nomenclatures): bool
    {
        return $nomenclatures->pluck('typeId')->unique()->count() === 1;
    }

    /**
     * Возвращает type первой номенклатуры как итоговый тип однотипного набора.
     */
    public function resolveType(Collection $nomenclatures): TypeData
    {
        /** @var NomenclatureData $first */
        $first = $nomenclatures->first();

        if ($first->type === null) {
            throw new KitCompositionException(
                'Не удалось определить тип комплекта: у номенклатуры не загружен type',
            );
        }

        return $first->type;
    }

    /**
     * Возвращает все номенклатуры, потому что однотипный набор не содержит вспомогательных типов.
     */
    public function primaryNomenclatures(Collection $nomenclatures): Collection
    {
        return $nomenclatures;
    }
}

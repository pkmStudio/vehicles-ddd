<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Application\Services\Strategies;

use App\Warehouse\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Комплект из номенклатур одного типа. Fallback-стратегия — регистрируется последней в
 * `KitPropertiesServiceProvider`, подходит для любого набора с единственным `typeId`.
 */
final readonly class SingleTypeStrategy implements KitCompositionStrategyInterface
{
    public function supports(Collection $nomenclatures): bool
    {
        return $nomenclatures->pluck('typeId')->unique()->count() === 1;
    }

    public function resolveType(Collection $nomenclatures): TypeData
    {
        /** @var NomenclatureData $first */
        $first = $nomenclatures->first();

        if ($first->type === null) {
            throw new UnexpectedValueException(
                'Не удалось определить тип комплекта: у номенклатуры не загружен type',
            );
        }

        return $first->type;
    }

    public function primaryNomenclatures(Collection $nomenclatures): Collection
    {
        return $nomenclatures;
    }
}

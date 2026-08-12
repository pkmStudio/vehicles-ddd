<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Packaging\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;

/**
 * Порт подбора/создания упаковки для набора номенклатур одного разрешённого типа.
 */
interface PackagingServiceInterface
{
    /**
     * Возвращает подходящий упаковочный размер, создавая новый при отсутствии подходящего.
     * Шаги:
     * 1) Определить detail template типа номенклатуры.
     * 2) Загрузить доступные упаковочные размеры этого типа.
     * 3) Выбрать стратегию, которая поддерживает template.
     * 4) Делегировать стратегии подбор существующей упаковки или создание новой.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     *
     * @throws PackDimensionNotResolvableException
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionData;
}

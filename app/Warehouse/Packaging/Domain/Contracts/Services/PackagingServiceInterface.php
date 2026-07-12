<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Domain\Contracts\Services;

use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;

/**
 * Порт подбора/создания упаковки для набора номенклатур одного разрешённого типа.
 */
interface PackagingServiceInterface
{
    /**
     * Возвращает подходящий упаковочный размер, создавая новый при отсутствии подходящего.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     *
     * @throws \App\Warehouse\Packaging\Domain\Exceptions\PackDimensionNotResolvableException
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionData;
}

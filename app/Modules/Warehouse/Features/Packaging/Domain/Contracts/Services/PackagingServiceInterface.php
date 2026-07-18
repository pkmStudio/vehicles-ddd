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
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     *
     * @throws PackDimensionNotResolvableException
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionData;
}

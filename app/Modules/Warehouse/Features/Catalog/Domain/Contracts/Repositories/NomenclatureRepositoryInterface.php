<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureIntegrationDeletionContextDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для Catalog-мутаций.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по typed lookup-критерию или null.
     */
    public function find(NomenclatureLookupDTO $lookup): ?NomenclatureData;

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, NomenclatureData>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Возвращает ids номенклатур бренда.
     *
     * @return Collection<int, int>
     */
    public function findIdsByBrandId(int $brandId): Collection;

    /**
     * Возвращает integration contexts, которые нужно передать в событие удаления.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection;
}

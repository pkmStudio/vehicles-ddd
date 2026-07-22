<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureIntegrationDeletionContextDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для Catalog-мутаций.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     */
    public function findById(int $id): ?NomenclatureData;

    /**
     * Возвращает первую номенклатуру по артикулу или null.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData;

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, NomenclatureData>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Возвращает integration contexts, которые нужно передать в событие удаления.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection;
}

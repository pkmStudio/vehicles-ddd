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
     * Возвращает номенклатуру по внутреннему идентификатору или null.
     *
     * Шаги:
     * 1. Принять внутренний id номенклатуры.
     * 2. Вернуть `NomenclatureData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?NomenclatureData;

    /**
     * Возвращает номенклатуру по артикулу или null.
     *
     * Шаги:
     * 1. Принять точный артикул номенклатуры.
     * 2. Вернуть `NomenclatureData` или `null`, если запись не найдена.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData;

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     *
     * Шаги:
     * 1. Принять список внутренних id номенклатур.
     * 2. Вернуть найденные DTO, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, NomenclatureData>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Возвращает ids номенклатур бренда.
     *
     * Шаги:
     * 1. Принять внутренний id бренда.
     * 2. Вернуть collection внутренних id номенклатур.
     *
     * @return Collection<int, int>
     */
    public function findIdsByBrandId(int $brandId): Collection;

    /**
     * Возвращает integration contexts, которые нужно передать в событие удаления.
     *
     * Шаги:
     * 1. Принять внутренний id номенклатуры.
     * 2. Вернуть collection контекстов для downstream deletion events.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection;
}

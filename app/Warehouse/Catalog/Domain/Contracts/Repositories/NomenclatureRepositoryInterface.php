<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Repositories;

use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\NomenclatureDeletionBlockersDTO;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\NomenclatureIntegrationDeletionContextDTO;
use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для Catalog-мутаций.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     */
    public function find(int $id): ?NomenclatureData;

    /**
     * Возвращает первую номенклатуру по артикулу или null.
     */
    public function firstByPartNumber(string $partNumber): ?NomenclatureData;

    /**
     * Проверяет, занят ли артикул другой номенклатурой.
     */
    public function partNumberExistsForAnother(string $partNumber, int $id): bool;

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, NomenclatureData>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Собирает зависимости, блокирующие удаление номенклатуры.
     */
    public function deletionBlockers(int $id): ?NomenclatureDeletionBlockersDTO;

    /**
     * Возвращает integration contexts, которые нужно передать в событие удаления.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection;
}

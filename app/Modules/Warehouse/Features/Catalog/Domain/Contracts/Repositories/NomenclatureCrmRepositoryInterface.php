<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read port номенклатуры для CRM API.
 */
interface NomenclatureCrmRepositoryInterface
{
    /**
     * Читает постраничный список номенклатур.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO с элементами и pagination meta.
     */
    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO;

    /**
     * Читает detail-снимок номенклатуры по id.
     *
     * Шаги:
     * 1. Принять внутренний id номенклатуры.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function findById(int $id): ?NomenclatureCrmListItemDTO;

    /**
     * Читает compact search options номенклатур.
     *
     * Шаги:
     * 1. Принять строку поиска и limit.
     * 2. Вернуть collection search DTO.
     *
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection;

    /**
     * Читает type options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Читает brand options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brandOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

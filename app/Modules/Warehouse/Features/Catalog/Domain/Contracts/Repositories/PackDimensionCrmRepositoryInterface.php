<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read port упаковочных размеров для CRM API.
 */
interface PackDimensionCrmRepositoryInterface
{
    /**
     * Читает постраничный список упаковочных размеров.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть DTO страницы с элементами и пагинацию meta.
     */
    public function paginate(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO;

    /**
     * Читает detail-снимок упаковочного размера по id.
     *
     * Шаги:
     * 1. Принять внутренний id упаковочного размера.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function findById(int $id): ?PackDimensionCrmListItemDTO;

    /**
     * Читает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Принять необязательную строку поиска, выбранный id и лимит.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

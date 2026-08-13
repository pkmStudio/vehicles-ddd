<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read-only клиент CRM сценариев упаковочных размеров Warehouse.
 */
interface PackDimensionCrmClientInterface
{
    /**
     * Возвращает постраничный список упаковочных размеров.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть DTO страницы, совместимый с CRM границу.
     */
    public function paginate(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO;

    /**
     * Возвращает detail-снимок упаковочного размера.
     *
     * Шаги:
     * 1. Принять внутренний id упаковочного размера.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?PackDimensionCrmListItemDTO;

    /**
     * Возвращает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Принять необязательную строку поиска, выбранный id и лимит.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

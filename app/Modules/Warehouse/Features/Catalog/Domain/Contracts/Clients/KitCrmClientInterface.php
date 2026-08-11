<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read-only client CRM сценариев комплектов Warehouse.
 */
interface KitCrmClientInterface
{
    /**
     * Возвращает постраничный список комплектов.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO, совместимый с CRM boundary.
     */
    public function paginate(KitCrmReadQueryDTO $query): KitCrmPageDTO;

    /**
     * Возвращает detail-снимок комплекта.
     *
     * Шаги:
     * 1. Принять внутренний id комплекта.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?KitCrmListItemDTO;

    /**
     * Возвращает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatures(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Возвращает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensions(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Возвращает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

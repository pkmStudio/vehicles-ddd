<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read port комплектов для CRM API.
 */
interface KitCrmRepositoryInterface
{
    /**
     * Читает постраничный список комплектов.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO с элементами и pagination meta.
     */
    public function paginate(KitCrmReadQueryDTO $query): KitCrmPageDTO;

    /**
     * Читает detail-снимок комплекта по id.
     *
     * Шаги:
     * 1. Принять внутренний id комплекта.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function findById(int $id): ?KitCrmListItemDTO;

    /**
     * Читает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatureOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Читает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensionOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Читает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

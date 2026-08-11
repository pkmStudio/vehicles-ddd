<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает CRM read-сценарии списка и options комплектов.
 */
interface ListKitsForCrmUseCaseInterface
{
    /**
     * Возвращает постраничный список комплектов.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO для CRM boundary.
     */
    public function execute(KitCrmReadQueryDTO $query): KitCrmPageDTO;

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

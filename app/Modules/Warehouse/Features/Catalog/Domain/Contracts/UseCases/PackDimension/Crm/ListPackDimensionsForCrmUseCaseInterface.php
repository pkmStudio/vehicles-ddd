<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает CRM read-сценарии списка и options упаковочных размеров.
 */
interface ListPackDimensionsForCrmUseCaseInterface
{
    /**
     * Возвращает постраничный список упаковочных размеров.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO для CRM boundary.
     */
    public function execute(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO;

    /**
     * Возвращает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

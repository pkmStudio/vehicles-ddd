<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария создания Warehouse-номенклатуры.
 */
interface CreateNomenclatureUseCaseInterface
{
    /**
     * Выполняет создание Warehouse-номенклатуры.
     *
     * Шаги:
     * 1) Принять CreateNomenclatureRequestDTO из boundary handler.
     * 2) Создать номенклатуру после проверки type/brand и уникальности part_number.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(CreateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}

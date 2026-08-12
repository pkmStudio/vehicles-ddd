<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт сценария удаления Warehouse-номенклатуры.
 */
interface DeleteNomenclatureUseCaseInterface
{
    /**
     * Выполняет удаление Warehouse-номенклатуры.
     *
     * Шаги:
     * 1) Принять DeleteNomenclatureRequestDTO из boundary handler.
     * 2) Удалить номенклатуру и сообщить контекст интеграционного удаления.
     * 3) Вернуть result DTO или null для повторного operation_id.
     */
    public function execute(DeleteNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO;
}

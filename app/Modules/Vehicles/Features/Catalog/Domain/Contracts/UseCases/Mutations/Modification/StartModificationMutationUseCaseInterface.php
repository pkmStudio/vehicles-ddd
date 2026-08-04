<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;

/**
 * Описывает порт сценария мутации модификаций из внешнего сообщения.
 */
interface StartModificationMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации модификаций по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO;
}

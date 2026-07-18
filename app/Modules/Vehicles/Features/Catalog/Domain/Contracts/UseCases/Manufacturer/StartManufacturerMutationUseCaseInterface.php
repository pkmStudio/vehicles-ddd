<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;

/**
 * Описывает порт сценария мутации производителей из внешнего сообщения.
 */
interface StartManufacturerMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации производителей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO;
}

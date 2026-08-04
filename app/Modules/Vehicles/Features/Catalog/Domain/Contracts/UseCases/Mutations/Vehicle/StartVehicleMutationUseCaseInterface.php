<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;

/**
 * Описывает порт сценария мутации автомобилей из внешнего сообщения.
 */
interface StartVehicleMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации автомобилей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO;
}

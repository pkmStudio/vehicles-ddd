<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;

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

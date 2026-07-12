<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\UpdateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\VehicleMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class StartVehicleMutationUseCase implements StartVehicleMutationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private CreateVehicleUseCaseInterface $createVehicle,
        private UpdateVehicleUseCaseInterface $updateVehicle,
        private DeleteVehicleUseCaseInterface $deleteVehicle,
    ) {}

    /**
     * Запускает сценарий мутации автомобилей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            VehicleMutationOperationEnum::Create => $this->create($request),
            VehicleMutationOperationEnum::Update => $this->update($request),
            VehicleMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Создает запись автомобилей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    private function create(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createVehicle->execute($createRequest);
    }

    /**
     * Обновляет запись автомобилей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    private function update(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateVehicle->execute($updateRequest);
    }

    /**
     * Удаляет запись автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function delete(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteVehicle->execute($deleteRequest);
    }

    /**
     * Собирает DTO конкретной операции автомобилей из общего DTO или payload.
     */
    private function createRequest(VehicleMutationRequestDTO $request): CreateVehicleRequestDTO
    {
        return $request->request;
    }

    /**
     * Собирает DTO конкретной операции автомобилей из общего DTO или payload.
     */
    private function updateRequest(VehicleMutationRequestDTO $request): UpdateVehicleRequestDTO
    {
        return $request->request;
    }

    /**
     * Удаляет запись автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(VehicleMutationRequestDTO $request): DeleteVehicleRequestDTO
    {
        return $request->request;
    }
}

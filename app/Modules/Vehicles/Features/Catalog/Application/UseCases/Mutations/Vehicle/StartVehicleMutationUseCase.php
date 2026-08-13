<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\CreateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\DeleteVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\UpdateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class StartVehicleMutationUseCase implements StartVehicleMutationUseCaseInterface
{
    /**
     * Получает use cases для трех поддерживаемых операций vehicle mutation.
     *
     * Шаги:
     * 1) Принять create-сценарий автомобиля.
     * 2) Принять update-сценарий автомобиля.
     * 3) Принять delete-сценарий автомобиля.
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
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
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
     * Извлекает create request из общего DTO vehicle mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как CreateVehicleRequestDTO для create use case.
     */
    private function createRequest(VehicleMutationRequestDTO $request): CreateVehicleRequestDTO
    {
        return $request->request;
    }

    /**
     * Извлекает update request из общего DTO vehicle mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как UpdateVehicleRequestDTO для update use case.
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

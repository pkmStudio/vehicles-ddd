<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации производителей из внешнего сообщения.
 */
final readonly class StartManufacturerMutationUseCase implements StartManufacturerMutationUseCaseInterface
{
    /**
     * Получает use cases для трех поддерживаемых операций manufacturer mutation.
     *
     * Шаги:
     * 1) Принять create-сценарий производителя.
     * 2) Принять update-сценарий производителя.
     * 3) Принять delete-сценарий производителя.
     */
    public function __construct(
        private CreateManufacturerUseCaseInterface $createManufacturer,
        private UpdateManufacturerUseCaseInterface $updateManufacturer,
        private DeleteManufacturerUseCaseInterface $deleteManufacturer,
    ) {}

    /**
     * Запускает сценарий мутации производителей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Создает запись производителей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    private function create(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createManufacturer->execute($createRequest);
    }

    /**
     * Обновляет запись производителей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    private function update(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateManufacturer->execute($updateRequest);
    }

    /**
     * Удаляет запись производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function delete(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteManufacturer->execute($deleteRequest);
    }

    /**
     * Извлекает create request из общего DTO manufacturer mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как CreateManufacturerRequestDTO для create use case.
     */
    private function createRequest(ManufacturerMutationRequestDTO $request): CreateManufacturerRequestDTO
    {
        return $request->request;
    }

    /**
     * Извлекает update request из общего DTO manufacturer mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как UpdateManufacturerRequestDTO для update use case.
     */
    private function updateRequest(ManufacturerMutationRequestDTO $request): UpdateManufacturerRequestDTO
    {
        return $request->request;
    }

    /**
     * Удаляет запись производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(ManufacturerMutationRequestDTO $request): DeleteManufacturerRequestDTO
    {
        return $request->request;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\CreateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\DeleteEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\StartEngineMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\UpdateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\CreateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации двигателей из внешнего сообщения.
 */
final readonly class StartEngineMutationUseCase implements StartEngineMutationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private CreateEngineUseCaseInterface $createEngine,
        private UpdateEngineUseCaseInterface $updateEngine,
        private DeleteEngineUseCaseInterface $deleteEngine,
    ) {}

    /**
     * Запускает сценарий мутации двигателей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Создает запись двигателей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    private function create(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createEngine->execute($createRequest);
    }

    /**
     * Обновляет запись двигателей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    private function update(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateEngine->execute($updateRequest);
    }

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function delete(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteEngine->execute($deleteRequest);
    }

    /**
     * Собирает DTO конкретной операции двигателей из общего DTO или payload.
     */
    private function createRequest(EngineMutationRequestDTO $request): CreateEngineRequestDTO
    {
        return $request->request;
    }

    /**
     * Собирает DTO конкретной операции двигателей из общего DTO или payload.
     */
    private function updateRequest(EngineMutationRequestDTO $request): UpdateEngineRequestDTO
    {
        return $request->request;
    }

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(EngineMutationRequestDTO $request): DeleteEngineRequestDTO
    {
        return $request->request;
    }
}

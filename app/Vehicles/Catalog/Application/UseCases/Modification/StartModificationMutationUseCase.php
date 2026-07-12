<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Modification;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\StartModificationMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\UpdateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class StartModificationMutationUseCase implements StartModificationMutationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private CreateModificationUseCaseInterface $createModification,
        private UpdateModificationUseCaseInterface $updateModification,
        private DeleteModificationUseCaseInterface $deleteModification,
    ) {}

    /**
     * Запускает сценарий мутации модификаций по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Создает запись модификаций.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    private function create(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createModification->execute($createRequest);
    }

    /**
     * Обновляет запись модификаций.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    private function update(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateModification->execute($updateRequest);
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function delete(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteModification->execute($deleteRequest);
    }

    /**
     * Собирает DTO конкретной операции модификаций из общего DTO или payload.
     */
    private function createRequest(ModificationMutationRequestDTO $request): CreateModificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Собирает DTO конкретной операции модификаций из общего DTO или payload.
     */
    private function updateRequest(ModificationMutationRequestDTO $request): UpdateModificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(ModificationMutationRequestDTO $request): DeleteModificationRequestDTO
    {
        return $request->request;
    }
}

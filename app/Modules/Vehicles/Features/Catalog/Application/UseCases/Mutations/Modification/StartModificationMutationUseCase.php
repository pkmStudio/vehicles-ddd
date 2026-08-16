<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class StartModificationMutationUseCase
{
    /**
     * Получает use cases для трех поддерживаемых операций modification mutation.
     *
     * Шаги:
     * 1) Принять create-сценарий модификации.
     * 2) Принять update-сценарий модификации.
     * 3) Принять delete-сценарий модификации.
     */
    public function __construct(
        private CreateModificationUseCase $createModification,
        private UpdateModificationUseCase $updateModification,
        private DeleteModificationUseCase $deleteModification,
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
     * Извлекает create request из общего DTO modification mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как CreateModificationRequestDTO для create use case.
     */
    private function createRequest(ModificationMutationRequestDTO $request): CreateModificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Извлекает update request из общего DTO modification mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как UpdateModificationRequestDTO для update use case.
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

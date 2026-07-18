<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\PartSpecification;

use App\Vehicles\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationDeleted;
use Throwable;

/**
 * Оркестрирует сценарий удаления спецификаций деталей из внешнего сообщения.
 */
final readonly class DeletePartSpecificationUseCase implements DeletePartSpecificationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Выполняет сценарий мутации спецификаций деталей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(DeletePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->specifications->firstById($request->id) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::PartSpecification,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->id,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $this->command->deleteById($request->id);

            event(new PartSpecificationDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                specificationId: $request->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::PartSpecification,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->id,
                recordId: $request->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::PartSpecification,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->id,
            );

            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use Throwable;

/**
 * Оркестрирует сценарий создания спецификаций деталей из внешнего сообщения.
 */
final readonly class CreatePartSpecificationUseCase implements CreatePartSpecificationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationCommandInterface $command,
        private PartSpecificationOwnerResolverFactoryInterface $ownerResolvers,
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
    public function execute(CreatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->specifications->firstById($request->id) !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::PartSpecification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->id,
                    reason: CatalogMutationRejectReasonEnum::AlreadyExists,
                );
            }

            $resolution = $this->ownerResolvers->make($request->owner->type)->execute($request->owner);
            if ($resolution->owner === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::PartSpecification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->id,
                    reason: $resolution->rejectReason ?? CatalogMutationRejectReasonEnum::OwnerNotFound,
                );
            }

            $specificationData = new PartSpecificationData(
                id: $request->id,
                partableType: $resolution->owner->type,
                partableId: $resolution->owner->partableId,
                template: $request->template,
                details: $request->details,
                featureValueId: $request->featureValueId,
                name: $request->name,
                text: $request->text,
            );

            $specification = $this->command->create($specificationData);

            event(new PartSpecificationCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                specification: $specification->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::PartSpecification,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $specification->id,
                recordId: $specification->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::PartSpecification,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $request->id,
            );

            throw $e;
        }
    }
}

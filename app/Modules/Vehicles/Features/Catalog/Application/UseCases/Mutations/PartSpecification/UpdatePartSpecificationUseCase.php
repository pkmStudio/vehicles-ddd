<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationDetailsWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\UpdatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationDetailsWriteResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\ResolvedPartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationUpdated;
use Throwable;

/**
 * Оркестрирует сценарий обновления спецификаций деталей из внешнего сообщения.
 */
final readonly class UpdatePartSpecificationUseCase implements UpdatePartSpecificationUseCaseInterface
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
        private PartSpecificationDetailsWritePolicyInterface $detailsPolicy,
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
    public function execute(UpdatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $existingSpecification = $this->rejectIfMissing($request);
            if ($existingSpecification instanceof CatalogMutationResultDTO) {
                return $existingSpecification;
            }

            $detailsResult = $this->applyDetailsPolicy($request);
            if (! $detailsResult->valid) {
                return $this->rejected(
                    request: $request,
                    reason: CatalogMutationRejectReasonEnum::InvalidDetails,
                    errors: $detailsResult->errors,
                    recordId: $existingSpecification->id,
                );
            }

            $resolution = $this->resolveOwner($request);
            if ($resolution->owner === null) {
                return $this->rejected(
                    request: $request,
                    reason: $resolution->rejectReason ?? CatalogMutationRejectReasonEnum::OwnerNotFound,
                );
            }

            $specificationData = $this->buildSpecificationData(
                request: $request,
                owner: $resolution->owner,
                details: $detailsResult->details,
            );

            $specification = $this->command->update($specificationData);
            $this->publishUpdatedEvent($request, $specification);

            return $this->completed($request, $specification);
        } catch (Throwable $e) {
            $this->failed($request);

            throw $e;
        }
    }

    /**
     * Находит существующую спецификацию или возвращает rejected result.
     */
    private function rejectIfMissing(UpdatePartSpecificationRequestDTO $request): PartSpecificationData|CatalogMutationResultDTO
    {
        $existingSpecification = $this->specifications->findById($request->id);
        if ($existingSpecification !== null) {
            return $existingSpecification;
        }

        return $this->rejected(
            request: $request,
            reason: CatalogMutationRejectReasonEnum::NotFound,
        );
    }

    /**
     * Применяет details policy до owner resolution, чтобы invalid details не давали side effects.
     */
    private function applyDetailsPolicy(
        UpdatePartSpecificationRequestDTO $request,
    ): PartSpecificationDetailsWriteResultDTO {
        return $this->detailsPolicy->apply(
            details: $request->details,
            template: $request->template,
            ownerType: $request->owner->type,
            partSpecificationId: $request->id,
            operationId: $request->operationId,
        );
    }

    /**
     * Разрешает владельца спецификации во внутренний id записи.
     */
    private function resolveOwner(UpdatePartSpecificationRequestDTO $request): PartSpecificationOwnerResolutionDTO
    {
        return $this->ownerResolvers->make($request->owner->type)->execute($request->owner);
    }

    /**
     * Собирает Data-снимок спецификации для записи через command.
     *
     * @param  array<string, mixed>  $details
     */
    private function buildSpecificationData(
        UpdatePartSpecificationRequestDTO $request,
        ResolvedPartSpecificationOwnerDTO $owner,
        array $details,
    ): PartSpecificationData {
        return new PartSpecificationData(
            id: $request->id,
            partableType: $owner->type,
            partableId: $owner->partableId,
            template: $request->template,
            details: $details,
            featureValueId: $request->featureValueId,
            name: $request->name,
            text: $request->text,
        );
    }

    /**
     * Публикует факт обновления спецификации.
     */
    private function publishUpdatedEvent(
        UpdatePartSpecificationRequestDTO $request,
        PartSpecificationData $specification,
    ): void {
        event(new PartSpecificationUpdated(
            userId: $request->userId,
            operationId: $request->operationId,
            specification: $specification->toArray(),
        ));
    }

    /**
     * Собирает completed result для update-сценария.
     */
    private function completed(
        UpdatePartSpecificationRequestDTO $request,
        PartSpecificationData $specification,
    ): CatalogMutationResultDTO {
        return $this->results->completed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $specification->id,
            recordId: $specification->id,
        );
    }

    /**
     * Собирает rejected result для update-сценария.
     *
     * @param  array<int, array{field: string, rule: string, message: string}>  $errors
     */
    private function rejected(
        UpdatePartSpecificationRequestDTO $request,
        CatalogMutationRejectReasonEnum $reason,
        array $errors = [],
        ?int $recordId = null,
    ): CatalogMutationResultDTO {
        return $this->results->rejected(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $request->id,
            reason: $reason,
            errors: $errors,
            recordId: $recordId,
        );
    }

    /**
     * Откатывает idempotency guard и публикует failed result перед пробросом исключения.
     */
    private function failed(UpdatePartSpecificationRequestDTO $request): void
    {
        $this->cache->forgetAccepted($request->operationId);
        $this->results->failed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $request->id,
        );
    }
}

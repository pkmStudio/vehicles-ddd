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
     * Получает порты, нужные для безопасного update part specification workflow.
     *
     * Шаги:
     * 1) Принять repository для проверки существования specification.
     * 2) Принять command записи и factory resolver-ов владельца.
     * 3) Принять cache/result/detail-policy порты для идемпотентности, result event и проверки details.
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
     *
     * Шаги:
     * 1) Найти specification по id из update request.
     * 2) Вернуть существующий Data snapshot, если запись найдена.
     * 3) Иначе вернуть rejected NotFound result без выполнения owner/details side effects.
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
     *
     * Шаги:
     * 1) Передать raw details, template и owner type в details write policy.
     * 2) Передать id/operation id для корректного rejection context.
     * 3) Вернуть normalized details или invalid result без изменения owner-записей.
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
     *
     * Шаги:
     * 1) Выбрать owner resolver по partable type из request.
     * 2) Передать owner DTO в resolver, который может найти, создать или обновить владельца.
     * 3) Вернуть resolved owner или reject reason.
     */
    private function resolveOwner(UpdatePartSpecificationRequestDTO $request): PartSpecificationOwnerResolutionDTO
    {
        return $this->ownerResolvers->make($request->owner->type)->execute($request->owner);
    }

    /**
     * Собирает Data-снимок спецификации для записи через command.
     *
     * Шаги:
     * 1) Использовать id обновляемой specification из request.
     * 2) Использовать resolved owner type/id вместо внешнего owner id.
     * 3) Записать template, normalized details и optional descriptive fields.
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
     *
     * Шаги:
     * 1) Сериализовать обновленный PartSpecificationData в payload события.
     * 2) Опубликовать module-level факт PartSpecificationUpdated с user/operation correlation.
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
     *
     * Шаги:
     * 1) Использовать id обновленной specification как externalId и recordId результата.
     * 2) Собрать completed result для entity PartSpecification и операции Update.
     * 3) Делегировать публикацию result service.
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
     * Шаги:
     * 1) Использовать id specification из request как externalId результата.
     * 2) Передать reason, field errors и optional recordId в result service.
     * 3) Вернуть опубликованный rejected result для update operation.
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
     *
     * Шаги:
     * 1) Освободить operation id в cache, чтобы сообщение можно было повторить.
     * 2) Опубликовать failed result с id обновляемой specification.
     * 3) Оставить проброс исходного исключения вызывающему execute.
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

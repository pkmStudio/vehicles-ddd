<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationDetailsWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationDetailsWriteResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\ResolvedPartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use Throwable;

/**
 * Оркестрирует сценарий создания спецификаций деталей из внешнего сообщения.
 */
final readonly class CreatePartSpecificationUseCase implements CreatePartSpecificationUseCaseInterface
{
    /**
     * Получает порты, нужные для безопасного create part specification workflow.
     *
     * Шаги:
     * 1) Принять repository для проверки дубликата по id.
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
    public function execute(CreatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $duplicateReject = $this->rejectIfAlreadyExists($request);
            if ($duplicateReject !== null) {
                return $duplicateReject;
            }

            $detailsResult = $this->applyDetailsPolicy($request);
            if (! $detailsResult->valid) {
                return $this->rejected(
                    request: $request,
                    reason: CatalogMutationRejectReasonEnum::InvalidDetails,
                    errors: $detailsResult->errors,
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

            $specification = $this->command->create($specificationData);
            $this->publishCreatedEvent($request, $specification);

            return $this->completed($request, $specification);
        } catch (Throwable $e) {
            $this->failed($request);

            throw $e;
        }
    }

    /**
     * Отклоняет create, если спецификация с переданным id уже существует.
     *
     * Шаги:
     * 1) Если внешний payload не содержит id, разрешить создание с auto-generated id.
     * 2) Если id передан, проверить наличие такой specification.
     * 3) Вернуть rejected AlreadyExists только при найденном дубликате.
     */
    private function rejectIfAlreadyExists(CreatePartSpecificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if ($request->id === null || $this->specifications->findById($request->id) === null) {
            return null;
        }

        return $this->rejected(
            request: $request,
            reason: CatalogMutationRejectReasonEnum::AlreadyExists,
        );
    }

    /**
     * Применяет details policy до owner resolution, чтобы invalid details не давали side effects.
     *
     * Шаги:
     * 1) Передать raw details, template и owner type в details write policy.
     * 2) Передать id/operation id для корректного rejection context.
     * 3) Вернуть normalized details или invalid result без создания owner-записей.
     */
    private function applyDetailsPolicy(
        CreatePartSpecificationRequestDTO $request,
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
     * 2) Передать owner DTO в resolver, который может найти или создать владельца.
     * 3) Вернуть resolved owner или reject reason.
     */
    private function resolveOwner(CreatePartSpecificationRequestDTO $request): PartSpecificationOwnerResolutionDTO
    {
        return $this->ownerResolvers->make($request->owner->type)->execute($request->owner);
    }

    /**
     * Собирает Data-снимок спецификации для записи через command.
     *
     * Шаги:
     * 1) Взять id из create request, если внешний payload его передал.
     * 2) Использовать resolved owner type/id вместо внешнего owner id.
     * 3) Записать template, normalized details и optional descriptive fields.
     *
     * @param  array<string, mixed>  $details
     */
    private function buildSpecificationData(
        CreatePartSpecificationRequestDTO $request,
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
     * Публикует факт создания спецификации.
     *
     * Шаги:
     * 1) Сериализовать созданный PartSpecificationData в payload события.
     * 2) Опубликовать module-level факт PartSpecificationCreated с user/operation correlation.
     */
    private function publishCreatedEvent(
        CreatePartSpecificationRequestDTO $request,
        PartSpecificationData $specification,
    ): void {
        event(new PartSpecificationCreated(
            userId: $request->userId,
            operationId: $request->operationId,
            specification: $specification->toArray(),
        ));
    }

    /**
     * Собирает completed result для create-сценария.
     *
     * Шаги:
     * 1) Использовать созданный id specification как externalId и recordId результата.
     * 2) Собрать completed result для entity PartSpecification и операции Create.
     * 3) Делегировать публикацию result service.
     */
    private function completed(
        CreatePartSpecificationRequestDTO $request,
        PartSpecificationData $specification,
    ): CatalogMutationResultDTO {
        return $this->results->completed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $specification->id,
            recordId: $specification->id,
        );
    }

    /**
     * Собирает rejected result для create-сценария.
     *
     * Шаги:
     * 1) Определить external id через id спецификации или external id владельца.
     * 2) Передать reason и field errors в result service.
     * 3) Вернуть опубликованный rejected result для create operation.
     *
     * @param  array<int, array{field: string, rule: string, message: string}>  $errors
     */
    private function rejected(
        CreatePartSpecificationRequestDTO $request,
        CatalogMutationRejectReasonEnum $reason,
        array $errors = [],
    ): CatalogMutationResultDTO {
        return $this->results->rejected(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $this->externalId($request),
            reason: $reason,
            errors: $errors,
        );
    }

    /**
     * Откатывает idempotency guard и публикует failed result перед пробросом исключения.
     *
     * Шаги:
     * 1) Освободить operation id в cache, чтобы сообщение можно было повторить.
     * 2) Опубликовать failed result с лучшим доступным external id.
     * 3) Оставить проброс исходного исключения вызывающему execute.
     */
    private function failed(CreatePartSpecificationRequestDTO $request): void
    {
        $this->cache->forgetAccepted($request->operationId);
        $this->results->failed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::PartSpecification,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $this->externalId($request),
        );
    }

    /**
     * Возвращает внешний id для результата мутации.
     *
     * Шаги:
     * 1) Если create payload содержит id specification, использовать его.
     * 2) Иначе использовать external id владельца как fallback correlation id.
     */
    private function externalId(CreatePartSpecificationRequestDTO $request): int
    {
        return $request->id ?? $request->owner->externalId;
    }
}

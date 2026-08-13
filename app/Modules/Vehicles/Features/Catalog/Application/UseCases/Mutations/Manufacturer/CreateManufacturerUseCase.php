<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use Throwable;

/**
 * Оркестрирует сценарий мутации производителей из внешнего сообщения.
 */
final readonly class CreateManufacturerUseCase implements CreateManufacturerUseCaseInterface
{
    /**
     * Получает порты create manufacturer workflow.
     *
     * Шаги:
     * 1) Принять repository для проверки дубликата по mfa_id.
     * 2) Принять command для записи производителя.
     * 3) Принять cache/result сервисы для идемпотентности и публикации результата.
     */
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Выполняет сценарий мутации производителей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(CreateManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existingManufacturer = $this->manufacturers->findByMfaId($request->mfaId);

            if ($existingManufacturer !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Manufacturer,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->mfaId,
                    reason: CatalogMutationRejectReasonEnum::AlreadyExists,
                );
            }

            $manufacturerData = new ManufacturerData(
                mfaId: $request->mfaId,
                name: $request->name,
                provider: $request->provider,
            );

            $manufacturer = $this->command->create($manufacturerData);

            event(new ManufacturerCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                manufacturer: $manufacturer->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $manufacturer->mfaId,
                recordId: $manufacturer->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $request->mfaId,
            );

            throw $e;
        }
    }
}

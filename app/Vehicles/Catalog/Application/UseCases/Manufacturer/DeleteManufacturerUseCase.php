<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации производителей из внешнего сообщения.
 */
final readonly class DeleteManufacturerUseCase implements DeleteManufacturerUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
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
    public function execute(DeleteManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $manufacturer = $this->manufacturers->firstByMfaId($request->mfaId);
            if ($manufacturer === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Manufacturer,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->mfaId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $vehicleCount = $this->manufacturers->vehicleCountByMfaId($request->mfaId);
            if ($vehicleCount === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Manufacturer,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->mfaId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            if ($vehicleCount > 0) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Manufacturer,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->mfaId,
                    reason: CatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: ['vehicles_count' => $vehicleCount],
                    recordId: $manufacturer->id,
                );
            }

            $this->command->deleteByMfaId($request->mfaId);
            event(new ManufacturerDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                mfaId: $request->mfaId,
                manufacturerId: (int) $manufacturer->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->mfaId,
                recordId: $manufacturer->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->mfaId,
            );

            throw $e;
        }
    }
}

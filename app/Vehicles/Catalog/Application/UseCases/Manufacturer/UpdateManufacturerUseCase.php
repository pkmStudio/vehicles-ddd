<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerUpdated;
use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;
use Throwable;

/**
 * Оркестрирует сценарий мутации производителей из внешнего сообщения.
 */
final readonly class UpdateManufacturerUseCase implements UpdateManufacturerUseCaseInterface
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
    public function execute(UpdateManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $existing = $this->manufacturers->firstByMfaId($request->mfaId);
            if ($existing === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Manufacturer,
                    operation: CatalogMutationOperationEnum::Update,
                    externalId: $request->mfaId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $manufacturerData = new ManufacturerData(
                mfaId: $request->mfaId,
                name: $request->name,
                provider: $request->provider,
                id: $existing->id,
            );

            $manufacturer = $this->command->update($manufacturerData);

            event(new ManufacturerUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                manufacturer: $manufacturer->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $manufacturer->mfaId,
                recordId: $manufacturer->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Manufacturer,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $request->mfaId,
            );

            throw $e;
        }
    }
}

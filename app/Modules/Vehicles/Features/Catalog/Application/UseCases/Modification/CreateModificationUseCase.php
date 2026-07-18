<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use Throwable;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class CreateModificationUseCase implements CreateModificationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private VehicleRepositoryInterface $vehicles,
        private ModificationCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Выполняет сценарий мутации модификаций.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(CreateModificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->modifications->firstByModIdAndType($request->modId, $request->type->value) !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::AlreadyExists,
                );
            }

            $vehicleId = $this->vehicles->vehicleIdByMsId($request->msId);
            if ($vehicleId === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::VehicleNotFound,
                );
            }

            $modificationData = new ModificationData(
                modId: $request->modId,
                type: $request->type,
                vehicleId: $vehicleId,
                msId: $request->msId,
                yearFrom: $request->yearFrom,
                yearTo: $request->yearTo,
                description: $request->description,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
            );

            $modification = $this->command->create($modificationData);

            event(new ModificationCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                modification: $modification->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $modification->modId,
                recordId: $modification->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $request->modId,
            );

            throw $e;
        }
    }
}

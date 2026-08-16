<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ModificationEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\ModificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\ModificationWritePolicy;
use Throwable;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class CreateModificationUseCase
{
    /**
     * Получает порты create modification workflow.
     *
     * Шаги:
     * 1) Принять repositories для проверки mod_id/type, генерации own mod_id, поиска vehicle и engine.
     * 2) Принять commands для записи modification, engine и engine_modification связей.
     * 3) Принять cache/result сервисы для идемпотентности и публикации результата.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private EngineRepositoryInterface $engines,
        private VehicleRepositoryInterface $vehicles,
        private ModificationCommandInterface $command,
        private EngineModificationCommandInterface $engineModifications,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
        private ModificationWritePolicy $writePolicy,
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
        $operationAccepted = $this->cache->accept($request->operationId);
        if (! $operationAccepted) {
            return null;
        }

        $modId = $request->modId ?? 0;

        try {
            $modId = $request->modId ?? $this->modifications->nextOwnModId();
            $existingModification = $this->modifications->findByModIdAndType($modId, $request->type->value);
            if ($existingModification !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $modId,
                    reason: CatalogMutationRejectReasonEnum::AlreadyExists,
                );
            }

            $vehicle = $this->vehicles->findByMsId($request->msId);
            if ($vehicle === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $modId,
                    reason: CatalogMutationRejectReasonEnum::VehicleNotFound,
                );
            }

            $engines = [];
            if ($request->syncEngines) {
                $engines = $this->existingEngines($request->engines);
                if ($engines === null) {
                    return $this->results->rejected(
                        userId: $request->userId,
                        operationId: $request->operationId,
                        entity: CatalogEntityEnum::Modification,
                        operation: CatalogMutationOperationEnum::Create,
                        externalId: $modId,
                        reason: CatalogMutationRejectReasonEnum::NotFound,
                    );
                }
            }

            $modificationData = new ModificationData(
                modId: $modId,
                type: $request->type,
                vehicleId: (int) $vehicle->id,
                msId: $request->msId,
                provider: $request->provider,
                yearFrom: $request->yearFrom,
                description: $request->description,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                allowChangeFields: $request->allowChangeFields,
                yearTo: $request->yearTo,
                descriptionShort: $request->descriptionShort,
                localizedName: $request->localizedName,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
            );

            $writeResult = $this->writePolicy->apply(
                incoming: ModificationWritePolicyResultDTO::fromArray($modificationData->toArray()),
                existing: null,
                sourceProvider: $request->provider,
            );
            $modification = $this->command->create(ModificationData::from($writeResult->toArray()));

            if ($request->syncEngines) {
                $this->engineModifications->syncForModification(
                    modification: $modification,
                    engines: $engines,
                );
            }

            $payload = new ModificationEventPayloadDTO(
                id: (int) $modification->id,
                modId: $modification->modId,
                type: $modification->type,
                vehicleId: $modification->vehicleId,
                msId: $modification->msId,
                provider: $modification->provider,
                yearFrom: $modification->yearFrom,
                description: $modification->description,
                powerPs: $modification->powerPs,
                powerKw: $modification->powerKw,
                engineType: $modification->engineType,
                allowChangeFields: $modification->allowChangeFields,
                yearTo: $modification->yearTo,
                descriptionShort: $modification->descriptionShort,
                localizedName: $modification->localizedName,
                gearType: $modification->gearType,
                driveType: $modification->driveType,
                brakeSystemType: $modification->brakeSystemType,
                numberOfCylinders: $modification->numberOfCylinders,
                capacityLt: $modification->capacityLt,
            );

            event(new ModificationCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                modification: $payload,
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
                externalId: $modId,
            );

            throw $e;
        }
    }

    /**
     * Возвращает только существующие двигатели, перечисленные во входящей modification mutation.
     *
     * Шаги:
     * 1) Для каждого engine request требовать внешний `eng_id`.
     * 2) Найти существующий двигатель по `eng_id`.
     * 3) Вернуть `null`, если хотя бы один двигатель не найден.
     * 4) Вернуть список существующих двигателей для синхронизации pivot-связей.
     *
     * @param  list<ModificationEngineRequestDTO>  $requests
     * @return list<EngineData>|null
     */
    private function existingEngines(array $requests): ?array
    {
        $engines = [];

        foreach ($requests as $request) {
            $engine = $this->engines->findByEngId($request->engId);
            if ($engine === null) {
                return null;
            }

            $engines[] = $engine;
        }

        return $engines;
    }
}

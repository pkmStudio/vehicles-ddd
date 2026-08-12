<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\CreateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use Throwable;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class CreateModificationUseCase implements CreateModificationUseCaseInterface
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
        private EngineCommandInterface $engineCommand,
        private EngineModificationCommandInterface $engineModifications,
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

            $modificationData = new ModificationData(
                modId: $modId,
                type: $request->type,
                vehicleId: (int) $vehicle->id,
                msId: $request->msId,
                yearFrom: $request->yearFrom,
                yearTo: $request->yearTo,
                description: $request->description,
                localizedName: $request->localizedName,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
                provider: $request->provider,
                allowChangeFields: $request->allowChangeFields,
            );

            $modification = $this->command->create($modificationData);

            if ($request->syncEngines) {
                $this->engineModifications->syncForModification(
                    modification: $modification,
                    engines: $this->upsertEngines($request->engines),
                );
            }

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
                externalId: $modId,
            );

            throw $e;
        }
    }

    /**
     * Создает или обновляет двигатели, перечисленные во входящей modification mutation.
     *
     * Шаги:
     * 1) Для каждого engine request определить eng_id: взять входящий или сгенерировать новый.
     * 2) Найти существующий двигатель по eng_id.
     * 3) Собрать EngineData с id существующей записи для update или без id для create.
     * 4) Выполнить create/update через engine command и накопить актуальные EngineData.
     * 5) Вернуть список двигателей для последующей синхронизации pivot-связей модификации.
     *
     * @param  list<ModificationEngineRequestDTO>  $requests
     * @return list<EngineData>
     */
    private function upsertEngines(array $requests): array
    {
        $engines = [];

        foreach ($requests as $request) {
            $engId = $request->engId ?? $this->engines->nextOwnEngId();
            $existing = $this->engines->findByEngId($engId);
            $data = new EngineData(
                engId: $engId,
                codeEngine: $request->codeEngine,
                powerKwStart: $request->powerKwStart,
                powerKwUpto: $request->powerKwUpto,
                powerPsStart: $request->powerPsStart,
                powerPsUpto: $request->powerPsUpto,
                engineCapacity: $request->engineCapacity,
                cylinderDiameter: $request->cylinderDiameter,
                cylinderCount: $request->cylinderCount,
                numberOfValves: $request->numberOfValves,
                fuelType: $request->fuelType,
                groupId: $request->groupId,
                provider: $request->provider,
                allowChangeFields: $request->allowChangeFields,
                id: $existing?->id,
            );

            $engines[] = $existing === null
                ? $this->engineCommand->create($data)
                : $this->engineCommand->update($data);
        }

        return $engines;
    }
}

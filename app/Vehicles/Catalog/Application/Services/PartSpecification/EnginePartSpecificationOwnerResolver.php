<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Services\PartSpecification;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerEngineDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\ResolvedPartSpecificationOwnerDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\ModelData\EngineData;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Разрешает двигатель-владелец PartSpecification, создавая или обновляя его при наличии payload.
 */
final readonly class EnginePartSpecificationOwnerResolver implements EnginePartSpecificationOwnerResolverInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
    ) {}

    /**
     * Разрешает владельца спеки во внутренний id записи.
     *
     * Шаги:
     * 1) Найти двигатель по external_id владельца.
     * 2) Если двигатель отсутствует, создать его из owner payload.
     * 3) Если двигатель существует и payload передан, обновить его.
     * 4) Вернуть внутренний id двигателя или причину отклонения.
     */
    public function execute(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO
    {
        $existing = $this->engines->firstByEngId($owner->externalId);
        if ($existing === null) {
            return $this->createOwner($owner);
        }

        if ($owner->engine === null) {
            return $this->resolved(
                externalId: $owner->externalId,
                partableId: (int) $existing->id,
            );
        }

        $engineData = $this->engineData(
            engId: $owner->externalId,
            payload: $owner->engine,
            id: $existing->id,
        );
        $engine = $this->command->update($engineData);

        return $this->resolved(
            externalId: $engine->engId,
            partableId: (int) $engine->id,
        );
    }

    /**
     * Создает отсутствующий двигатель-владелец или возвращает причину отказа.
     *
     * Шаги:
     * 1) Проверить наличие payload создания двигателя.
     * 2) Собрать EngineData из payload.
     * 3) Создать двигатель через Command и вернуть его внутренний id.
     */
    private function createOwner(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO
    {
        if ($owner->engine === null) {
            return new PartSpecificationOwnerResolutionDTO(
                owner: null,
                rejectReason: CatalogMutationRejectReasonEnum::OwnerNotFound,
            );
        }

        $engineData = $this->engineData(
            engId: $owner->externalId,
            payload: $owner->engine,
        );
        $engine = $this->command->create($engineData);

        return $this->resolved(
            externalId: $engine->engId,
            partableId: (int) $engine->id,
        );
    }

    /**
     * Собирает EngineData для создания или обновления владельца.
     */
    private function engineData(
        int $engId,
        PartSpecificationOwnerEngineDTO $payload,
        ?int $id = null,
    ): EngineData {
        return new EngineData(
            engId: $engId,
            codeEngine: $payload->codeEngine,
            engPowerKwStart: $payload->engPowerKwStart,
            engPowerKwUpto: $payload->engPowerKwUpto,
            engPowerPsStart: $payload->engPowerPsStart,
            engPowerPsUpto: $payload->engPowerPsUpto,
            engineCapacity: $payload->engineCapacity,
            cylinderDiameter: $payload->cylinderDiameter,
            cylinderCount: $payload->cylinderCount,
            engNumberOfValves: $payload->engNumberOfValves,
            engFuelType: $payload->engFuelType,
            groupId: $payload->groupId,
            id: $id,
        );
    }

    /**
     * Собирает успешный результат разрешения двигателя-владельца.
     */
    private function resolved(int $externalId, int $partableId): PartSpecificationOwnerResolutionDTO
    {
        $owner = new ResolvedPartSpecificationOwnerDTO(
            type: PartableTypeEnum::ENGINE,
            externalId: $externalId,
            partableId: $partableId,
        );

        return new PartSpecificationOwnerResolutionDTO(
            owner: $owner,
        );
    }
}

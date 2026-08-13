<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\ResolvedPartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Разрешает двигатель-владелец PartSpecification, создавая или обновляя его при наличии payload.
 */
final readonly class EnginePartSpecificationOwnerResolver implements EnginePartSpecificationOwnerResolverInterface
{
    /**
     * Получает read/write порты двигателя для разрешения владельца specification.
     *
     * Шаги:
     * 1) Принять repository для поиска engine по внешнему eng_id.
     * 2) Принять command для создания или обновления engine owner.
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
        $existing = $this->engines->findByEngId($owner->externalId);
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
     *
     * Шаги:
     * 1) Использовать external id владельца как eng_id каталожного двигателя.
     * 2) Перенести nullable технические характеристики из owner payload без дополнительных defaults.
     * 3) Проставить внутренний id только для update существующего двигателя.
     */
    private function engineData(
        int $engId,
        PartSpecificationOwnerEngineDTO $payload,
        ?int $id = null,
    ): EngineData {
        return new EngineData(
            engId: $engId,
            codeEngine: $payload->codeEngine,
            powerKwStart: $payload->powerKwStart,
            powerKwUpto: $payload->powerKwUpto,
            powerPsStart: $payload->powerPsStart,
            powerPsUpto: $payload->powerPsUpto,
            engineCapacity: $payload->engineCapacity,
            cylinderDiameter: $payload->cylinderDiameter,
            cylinderCount: $payload->cylinderCount,
            numberOfValves: $payload->numberOfValves,
            fuelType: $payload->fuelType,
            groupId: $payload->groupId,
            id: $id,
        );
    }

    /**
     * Собирает успешный результат разрешения двигателя-владельца.
     *
     * Шаги:
     * 1) Собрать resolved owner DTO с типом ENGINE, внешним id и внутренним partable id.
     * 2) Обернуть resolved owner в результат без reject reason.
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

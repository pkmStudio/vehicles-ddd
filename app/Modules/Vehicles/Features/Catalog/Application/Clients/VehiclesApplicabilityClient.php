<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehiclesApplicabilityRepositoryInterface;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\Exceptions\VehicleApplicabilityException;
use Illuminate\Support\Collection;

final readonly class VehiclesApplicabilityClient implements VehiclesApplicabilityClientInterface
{
    /**
     * Инициализирует read client Vehicles для Applicability.
     *
     * Шаги:
     * 1. Получает repository port owner-слоя Vehicles Catalog.
     * 2. Сохраняет port как единственный источник read-данных для client.
     */
    public function __construct(
        private VehiclesApplicabilityRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает передние спецификации дворников для расчета применяемости.
     *
     * Шаги:
     * 1. Принимает нормализованные параметры длины и количества щеток.
     * 2. Делегирует read-запрос repository port.
     * 3. Возвращает collection DTO без SQL внутри client.
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection
    {
        return $this->vehicles->frontWiperSpecifications(
            lengthMain: $lengthMain,
            lengthSecond: $lengthSecond,
            countWipers: $countWipers,
        );
    }

    /**
     * Возвращает задние спецификации дворников для расчета применяемости.
     *
     * Шаги:
     * 1. Принимает нормализованные параметры длины и количества щеток.
     * 2. Делегирует read-запрос repository port.
     * 3. Возвращает collection DTO без SQL внутри client.
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection
    {
        return $this->vehicles->rearWiperSpecifications(
            lengthMain: $lengthMain,
            countWipers: $countWipers,
        );
    }

    /**
     * Разрешает внутренний id модификации по внешним `ms_id` и `mod_id`.
     *
     * Шаги:
     * 1. Ищет Vehicle по `ms_id` через repository port.
     * 2. Пытается найти модификацию внутри найденного Vehicle.
     * 3. При отсутствии прямого совпадения проверяет parent Vehicle.
     * 4. Выбрасывает `VehicleApplicabilityException`, если модель или модификация не найдены.
     */
    public function resolveModificationIdByMsAndModId(int $msId, int $modId): int
    {
        $vehicle = $this->vehicles->findVehicleByMsId($msId);

        if ($vehicle === null) {
            throw new VehicleApplicabilityException("Модель (ms_id: {$msId}) не найдена.");
        }

        $modificationId = $this->vehicles->findModificationIdByMsAndModId($vehicle->msId, $modId);

        if ($modificationId !== null) {
            return $modificationId;
        }

        if ($vehicle->parentId !== null) {
            return $this->resolveParentModificationId($vehicle->msId, $vehicle->parentId, $modId);
        }

        throw new VehicleApplicabilityException("Модификация (ms_id: {$vehicle->msId}, mod_id: {$modId}) не найдена.");
    }

    /**
     * Ищет модификацию у родительской модели Vehicles.
     *
     * Шаги:
     * 1. Разрешает `parentId` во внешний `parent_ms_id`.
     * 2. Ищет модификацию по `parent_ms_id` и `mod_id`.
     * 3. Возвращает найденный id или выбрасывает доменное исключение client boundary.
     */
    private function resolveParentModificationId(int $vehicleMsId, int $parentId, int $modId): int
    {
        $parentMsId = $this->vehicles->findVehicleMsIdById($parentId);

        if ($parentMsId === null) {
            throw new VehicleApplicabilityException("Модификация (ms_id: {$vehicleMsId}, mod_id: {$modId}) не найдена.");
        }

        $modificationId = $this->vehicles->findModificationIdByMsAndModId($parentMsId, $modId);

        if ($modificationId !== null) {
            return $modificationId;
        }

        throw new VehicleApplicabilityException(
            "Модификация (ms_id: {$vehicleMsId}, mod_id: {$modId}) не найдена ни у модели, ни у родителя (parent_ms_id: {$parentMsId}).",
        );
    }
}

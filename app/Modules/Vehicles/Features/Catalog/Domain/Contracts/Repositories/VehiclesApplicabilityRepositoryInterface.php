<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability\VehicleApplicabilityLookupDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read port Vehicles Catalog для Applicability.
 */
interface VehiclesApplicabilityRepositoryInterface
{
    /**
     * Читает передние спецификации дворников для расчета применяемости.
     *
     * Шаги:
     * 1. Принять длины и количество щеток.
     * 2. Вернуть collection подходящих спецификаций деталей.
     *
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection;

    /**
     * Читает задние спецификации дворников для расчета применяемости.
     *
     * Шаги:
     * 1. Принять длину и количество щеток.
     * 2. Вернуть collection подходящих спецификаций деталей.
     *
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection;

    /**
     * Читает lookup автомобиля по внешнему `ms_id`.
     *
     * Шаги:
     * 1. Принять внешний `ms_id` автомобиля.
     * 2. Вернуть lookup DTO или `null`, если запись не найдена.
     */
    public function findVehicleByMsId(int $msId): ?VehicleApplicabilityLookupDTO;

    /**
     * Читает внешний `ms_id` автомобиля по внутреннему id.
     *
     * Шаги:
     * 1. Принять внутренний id автомобиля.
     * 2. Вернуть `ms_id` или `null`, если запись не найдена.
     */
    public function findVehicleMsIdById(int $id): ?int;

    /**
     * Читает id модификации по внешним `ms_id` и `mod_id`.
     *
     * Шаги:
     * 1. Принять внешний `ms_id` автомобиля и `mod_id` модификации.
     * 2. Вернуть внутренний id модификации или `null`, если запись не найдена.
     */
    public function findModificationIdByMsAndModId(int $msId, int $modId): ?int;
}

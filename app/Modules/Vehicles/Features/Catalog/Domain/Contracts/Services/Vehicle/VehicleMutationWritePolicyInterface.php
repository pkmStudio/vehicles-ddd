<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationWriteContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;

/**
 * Описывает правила записи полей автомобиля для catalog mutation сценариев.
 */
interface VehicleMutationWritePolicyInterface
{
    /**
     * Применяет правила создания автомобиля из catalog mutation источника.
     */
    public function applyForCreate(
        VehicleData $incoming,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData;

    /**
     * Применяет правила обновления автомобиля из catalog mutation источника.
     */
    public function applyForUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData;

    /**
     * Возвращает true, если mutation может писать OD-managed поля существующего автомобиля.
     */
    public function allowsCatalogManagedFields(VehicleData $existing): bool;
}

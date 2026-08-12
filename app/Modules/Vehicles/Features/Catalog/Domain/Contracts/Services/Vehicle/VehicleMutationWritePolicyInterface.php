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
     *
     * Шаги:
     * 1) Принять входящий VehicleData и context create operation.
     * 2) Нормализовать поля, которыми владеет catalog mutation workflow.
     * 3) Вернуть snapshot для command create.
     */
    public function applyForCreate(
        VehicleData $incoming,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData;

    /**
     * Применяет правила обновления автомобиля из catalog mutation источника.
     *
     * Шаги:
     * 1) Принять incoming и existing snapshots.
     * 2) Сохранить locked поля для provider-owned записей.
     * 3) Вернуть merged snapshot для command update.
     */
    public function applyForUpdate(
        VehicleData $incoming,
        VehicleData $existing,
        VehicleMutationWriteContextDTO $context,
    ): VehicleData;

    /**
     * Возвращает true, если mutation может писать OD-managed поля существующего автомобиля.
     *
     * Шаги:
     * 1) Проверить provider существующего автомобиля.
     * 2) Разрешить catalog-managed поля только для OD-owned записей.
     */
    public function allowsCatalogManagedFields(VehicleData $existing): bool;
}

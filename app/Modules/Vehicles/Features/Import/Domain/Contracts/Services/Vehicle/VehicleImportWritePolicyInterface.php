<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleImportWriteContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

/**
 * Описывает правила записи автомобиля из import workflows.
 */
interface VehicleImportWritePolicyInterface
{
    /**
     * Применяет provider ownership правила к строке импорта.
     *
     * Шаги:
     * 1) Сравнить incoming row с существующей записью и import context.
     * 2) Решить, какие поля разрешено записать.
     * 3) Вернуть VehicleData для command layer.
     */
    public function apply(
        VehicleData $incoming,
        ?VehicleData $existing,
        VehicleImportWriteContextDTO $context,
    ): VehicleData;
}

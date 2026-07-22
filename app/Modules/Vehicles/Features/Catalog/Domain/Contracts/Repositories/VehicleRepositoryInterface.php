<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;

/**
 * Описывает порт чтения автомобилей из каталога.
 */
interface VehicleRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function findByMsId(int $msId): ?VehicleData;

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function vehicleIdByMsId(int $msId): ?int;

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function manufacturerIdByMfaId(int $mfaId): ?int;

}

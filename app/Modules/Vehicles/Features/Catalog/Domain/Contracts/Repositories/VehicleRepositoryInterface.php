<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения автомобилей из каталога.
 */
interface VehicleRepositoryInterface
{
    /**
     * Возвращает ТС по внутреннему идентификатору.
     */
    public function findById(int $id): ?VehicleData;

    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function findByMsId(int $msId): ?VehicleData;

    /**
     * Возвращает разрешённые ТС производителя.
     *
     * @return Collection<int, VehicleData>
     */
    public function findAllowedByManufacturerId(int $manufacturerId): Collection;

    /**
     * Возвращает следующий свободный внешний идентификатор автомобиля.
     */
    public function nextMsId(): int;
}

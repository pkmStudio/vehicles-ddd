<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleDeletionBlockersDTO;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;

/**
 * Описывает порт чтения автомобилей из каталога.
 */
interface VehicleRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function firstByMsId(int $msId): ?VehicleData;

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function vehicleIdByMsId(int $msId): ?int;

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function manufacturerIdByMfaId(int $mfaId): ?int;

    /**
     * Собирает зависимости, блокирующие удаление автомобилей.
     *
     * Шаги:
     * 1) Найти целевую запись по внешнему идентификатору.
     * 2) Посчитать связанные записи, которые нельзя удалить каскадом.
     * 3) Вернуть DTO или массив блокировок удаления.
     */
    public function deletionBlockersByMsId(int $msId): ?VehicleDeletionBlockersDTO;
}

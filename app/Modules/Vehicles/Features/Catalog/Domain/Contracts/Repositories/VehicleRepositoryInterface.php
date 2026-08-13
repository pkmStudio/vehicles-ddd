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
     *
     * Шаги:
     * 1. Принять внутренний id автомобиля.
     * 2. Вернуть `VehicleData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?VehicleData;

    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Принять внешний `ms_id` автомобиля.
     * 2. Вернуть первый `VehicleData` или `null`, если запись не найдена.
     */
    public function findByMsId(int $msId): ?VehicleData;

    /**
     * Возвращает разрешённые ТС производителя.
     *
     * Шаги:
     * 1. Принять внутренний id производителя.
     * 2. Вернуть collection разрешенных автомобилей производителя.
     *
     * @return Collection<int, VehicleData>
     */
    public function findAllowedByManufacturerId(int $manufacturerId): Collection;

    /**
     * Возвращает ids ТС производителя.
     *
     * Шаги:
     * 1. Принять внутренний id производителя.
     * 2. Вернуть collection внутренних id автомобилей.
     *
     * @return Collection<int, int>
     */
    public function findIdsByManufacturerId(int $manufacturerId): Collection;

    /**
     * Возвращает ids дочерних ТС по parent ids.
     *
     * Шаги:
     * 1. Принять список parent ids.
     * 2. Вернуть collection внутренних id дочерних автомобилей.
     *
     * @param  array<int, int>  $parentIds
     * @return Collection<int, int>
     */
    public function findChildIdsByParentIds(array $parentIds): Collection;

    /**
     * Возвращает следующий свободный внешний идентификатор автомобиля.
     *
     * Шаги:
     * 1. Найти минимальный существующий `ms_id`.
     * 2. Вернуть следующий отрицательный id для новой записи.
     */
    public function nextMsId(): int;
}

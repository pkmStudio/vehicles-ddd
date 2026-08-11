<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения модификаций из каталога.
 */
interface ModificationRepositoryInterface
{
    /**
     * Возвращает модификацию по внутреннему идентификатору.
     *
     * Шаги:
     * 1. Принять внутренний id модификации.
     * 2. Вернуть `ModificationData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?ModificationData;

    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1. Принять внешний `mod_id` и тип модификации.
     * 2. Вернуть первый `ModificationData` или `null`, если запись не найдена.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Возвращает модификации ТС.
     *
     * Шаги:
     * 1. Принять внутренний id автомобиля.
     * 2. Вернуть collection модификаций автомобиля.
     *
     * @return Collection<int, ModificationData>
     */
    public function findByVehicleId(int $vehicleId): Collection;

    /**
     * Возвращает ids модификаций по vehicle ids.
     *
     * Шаги:
     * 1. Принять список внутренних id автомобилей.
     * 2. Вернуть collection внутренних id модификаций.
     *
     * @param  array<int, int>  $vehicleIds
     * @return Collection<int, int>
     */
    public function findIdsByVehicleIds(array $vehicleIds): Collection;

    /**
     * Возвращает ids связок модификаций с двигателями.
     *
     * Шаги:
     * 1. Принять список внутренних id модификаций.
     * 2. Вернуть collection id связок модификаций с двигателями.
     *
     * @param  array<int, int>  $modificationIds
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByModificationIds(array $modificationIds): Collection;
}

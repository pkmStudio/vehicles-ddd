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
     */
    public function findById(int $id): ?ModificationData;

    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Возвращает модификации ТС.
     *
     * @return Collection<int, ModificationData>
     */
    public function findByVehicleId(int $vehicleId): Collection;

    /**
     * Возвращает ids модификаций по vehicle ids.
     *
     * @param  array<int, int>  $vehicleIds
     * @return Collection<int, int>
     */
    public function findIdsByVehicleIds(array $vehicleIds): Collection;

    /**
     * Возвращает ids связок модификаций с двигателями.
     *
     * @param  array<int, int>  $modificationIds
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByModificationIds(array $modificationIds): Collection;
}

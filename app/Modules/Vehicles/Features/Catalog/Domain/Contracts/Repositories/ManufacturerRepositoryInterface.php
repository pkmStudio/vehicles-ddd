<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения производителей из каталога.
 */
interface ManufacturerRepositoryInterface
{
    /**
     * Возвращает производителя по внутреннему идентификатору.
     */
    public function findById(int $id): ?ManufacturerData;

    /**
     * Возвращает первый Data-снимок производителей по внешнему идентификатору.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData;

    /**
     * Возвращает производителей, у которых есть разрешённые ТС.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function findAllWithAllowedVehicles(): Collection;
}

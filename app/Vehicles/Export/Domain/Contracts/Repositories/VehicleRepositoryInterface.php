<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Repositories;

use App\Vehicles\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface VehicleRepositoryInterface
{
    public function find(int $id): ?VehicleData;

    public function findOrFail(int $id): VehicleData;

    /** @return Collection<int, VehicleData> */
    public function all(): Collection;

    /**
     * Для основного листа экспорта (с маркой и родителем).
     *
     * @return Collection<int, VehicleData>
     */
    public function forMainSheet(bool $onlyAllowed): Collection;

    /**
     * Для листа дворников (со спецификациями шаблона wiper + их featureValue).
     *
     * @return Collection<int, VehicleData>
     */
    public function forWiperSheet(bool $onlyAllowed): Collection;
}

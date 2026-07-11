<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface VehicleRepositoryInterface
{
    public function find(int $id): ?VehicleData;

    public function findOrFail(int $id): VehicleData;

    /** @return Collection<int, VehicleData> */
    public function all(): Collection;

    public function firstByMsId(int $msId): ?VehicleData;

    /** Минимальный ms_id (для генерации отрицательных id новых ТС). 0 если таблица пуста. */
    public function minMsId(): int;

    /** ms_id родителя ТС с данным ms_id. null, если ТС не найдено или у него нет родителя. */
    public function parentMsId(int $msId): ?int;
}

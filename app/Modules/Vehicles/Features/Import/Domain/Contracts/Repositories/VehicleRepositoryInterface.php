<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleRepositoryInterface
{
    public function findByMsId(int $msId): ?VehicleData;

    /** ТС с минимальным ms_id (для генерации отрицательных id новых ТС). */
    public function findMinMsId(): ?VehicleData;

    /** ms_id родителя ТС с данным ms_id. null, если ТС не найдено или у него нет родителя. */
    public function parentMsId(int $msId): ?int;
}

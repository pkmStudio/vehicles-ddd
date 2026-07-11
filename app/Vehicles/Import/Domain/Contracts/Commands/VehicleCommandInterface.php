<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\VehicleData;

interface VehicleCommandInterface
{
    public function create(VehicleData $data): VehicleData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(VehicleData $data): VehicleData;

    /** Upsert по натуральному ключу ms_id. */
    public function upsertByMsId(VehicleData $data): VehicleData;

    public function delete(VehicleData $data): bool;
}

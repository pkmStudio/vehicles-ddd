<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface PartSpecificationCommandInterface
{
    public function create(PartSpecificationData $data): PartSpecificationData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(PartSpecificationData $data): PartSpecificationData;

    /** Upsert по натуральному ключу: partable + template + feature_value_id. */
    public function upsert(PartSpecificationData $data): PartSpecificationData;
}

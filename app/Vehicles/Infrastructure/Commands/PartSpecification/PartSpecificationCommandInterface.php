<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands\PartSpecification;

use App\Vehicles\Application\DTOs\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\PartSpecification;

interface PartSpecificationCommandInterface
{
    public function create(PartSpecificationData $data): PartSpecification;

    public function update(PartSpecification $specification, PartSpecificationData $data): PartSpecification;

    /** Upsert по натуральному ключу: partable + template + feature_value_id. */
    public function upsert(PartSpecificationData $data): PartSpecification;

    public function delete(PartSpecification $specification): bool;
}

<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Contracts;

use App\Vehicles\Models\PartSpecification;

/**
 * Запись PartSpecification (write).
 */
interface PartSpecificationCommandInterface
{
    public function create(array $attributes): PartSpecification;

    public function update(PartSpecification $partSpecification, array $attributes): PartSpecification;

    public function updateOrCreate(array $attributes, array $values = []): PartSpecification;

    public function delete(PartSpecification $partSpecification): bool;
}

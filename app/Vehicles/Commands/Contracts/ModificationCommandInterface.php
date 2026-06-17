<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Contracts;

use App\Vehicles\Models\Modification;

/**
 * Запись Modification (write).
 */
interface ModificationCommandInterface
{
    public function create(array $attributes): Modification;

    public function update(Modification $modification, array $attributes): Modification;

    public function updateOrCreate(array $attributes, array $values = []): Modification;

    public function delete(Modification $modification): bool;
}

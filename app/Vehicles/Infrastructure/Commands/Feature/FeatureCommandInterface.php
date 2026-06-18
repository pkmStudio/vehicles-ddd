<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands\Feature;

use App\Vehicles\Domain\Models\Feature;

/**
 * Запись Feature (write).
 */
interface FeatureCommandInterface
{
    public function create(array $attributes): Feature;

    public function update(Feature $feature, array $attributes): Feature;

    public function updateOrCreate(array $attributes, array $values = []): Feature;

    public function delete(Feature $feature): bool;
}

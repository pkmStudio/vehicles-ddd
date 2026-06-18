<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\FeatureValue;

use App\Vehicles\Models\FeatureValue;

/**
 * Запись FeatureValue (write).
 */
interface FeatureValueCommandInterface
{
    public function create(array $attributes): FeatureValue;

    public function update(FeatureValue $featureValue, array $attributes): FeatureValue;

    public function updateOrCreate(array $attributes, array $values = []): FeatureValue;

    public function delete(FeatureValue $featureValue): bool;
}

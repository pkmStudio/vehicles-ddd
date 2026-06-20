<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Commands;

use App\Vehicles\Domain\ModelData\FeatureValue\FeatureValueData;
use App\Vehicles\Domain\Models\FeatureValue;

/**
 * Запись FeatureValue (write). Вход — FeatureValueData (не сырой массив).
 */
interface FeatureValueCommandInterface
{
    public function create(FeatureValueData $data): FeatureValue;

    public function update(FeatureValue $featureValue, FeatureValueData $data): FeatureValue;

    public function upsertByName(FeatureValueData $data): FeatureValue;

    public function delete(FeatureValue $featureValue): bool;
}

<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Commands;

use App\Vehicles\Domain\ModelData\Feature\FeatureData;
use App\Vehicles\Domain\Models\Feature;

/**
 * Запись Feature (write). Вход — FeatureData (не сырой массив).
 */
interface FeatureCommandInterface
{
    public function create(FeatureData $data): Feature;

    public function update(Feature $feature, FeatureData $data): Feature;

    public function upsertByName(FeatureData $data): Feature;

    public function delete(Feature $feature): bool;
}

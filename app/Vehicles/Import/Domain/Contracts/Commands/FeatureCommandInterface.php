<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\Feature\FeatureData;

/**
 * Запись Feature (write). Вход — FeatureData (не сырой массив).
 */
interface FeatureCommandInterface
{
    public function create(FeatureData $data): FeatureData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(FeatureData $data): FeatureData;

    public function upsertByName(FeatureData $data): FeatureData;

    public function delete(FeatureData $data): bool;
}

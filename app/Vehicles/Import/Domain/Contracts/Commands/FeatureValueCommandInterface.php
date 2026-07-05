<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\FeatureValue\FeatureValueData;

/**
 * Запись FeatureValue (write). Вход — FeatureValueData (не сырой массив).
 */
interface FeatureValueCommandInterface
{
    public function create(FeatureValueData $data): FeatureValueData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(FeatureValueData $data): FeatureValueData;

    public function upsertByName(FeatureValueData $data): FeatureValueData;

    public function delete(FeatureValueData $data): bool;
}

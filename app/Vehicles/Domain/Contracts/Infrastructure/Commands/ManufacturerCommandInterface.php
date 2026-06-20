<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Commands;

use App\Vehicles\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Domain\Models\Manufacturer;

/**
 * Запись Manufacturer (write). Принимает типизированный DTO.
 */
interface ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): Manufacturer;

    public function update(Manufacturer $manufacturer, ManufacturerData $data): Manufacturer;

    /**
     * Upsert по натуральному ключу mfa_id.
     */
    public function upsertByMfaId(ManufacturerData $data): Manufacturer;

    /** Найти марку по имени или создать с заданным mfa_id. */
    public function firstOrCreateByName(string $name, int $mfaId): Manufacturer;

    /** Найти марку по mfa_id или создать с заданным именем. */
    public function firstOrCreateByMfaId(int $mfaId, string $name): Manufacturer;

    public function delete(Manufacturer $manufacturer): bool;
}

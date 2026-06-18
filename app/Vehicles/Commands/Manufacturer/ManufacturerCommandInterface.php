<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Manufacturer;

use App\Vehicles\DTOs\Manufacturer\ManufacturerData;
use App\Vehicles\Models\Manufacturer;

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

    public function delete(Manufacturer $manufacturer): bool;
}

<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\ManufacturerData;

/**
 * Запись Manufacturer (write). Принимает и отдаёт ManufacturerData — Eloquent-модель наружу
 * не выходит (деталь Infrastructure, см. plan.md §3).
 */
interface ManufacturerCommandInterface
{
    /**
     * Upsert по натуральному ключу mfa_id.
     */
    public function upsertByMfaId(ManufacturerData $data): ManufacturerData;
}

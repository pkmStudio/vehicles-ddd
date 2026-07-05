<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;

/**
 * Запись Manufacturer (write). Принимает и отдаёт ManufacturerData — Eloquent-модель наружу
 * не выходит (деталь Infrastructure, см. plan.md §3).
 */
interface ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): ManufacturerData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(ManufacturerData $data): ManufacturerData;

    /**
     * Upsert по натуральному ключу mfa_id.
     */
    public function upsertByMfaId(ManufacturerData $data): ManufacturerData;

    /** Найти марку по имени или создать с заданным mfa_id. */
    public function firstOrCreateByName(string $name, int $mfaId): ManufacturerData;

    /** Найти марку по mfa_id или создать с заданным именем. */
    public function firstOrCreateByMfaId(int $mfaId, string $name): ManufacturerData;

    public function delete(ManufacturerData $data): bool;
}

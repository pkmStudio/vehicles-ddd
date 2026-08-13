<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

/**
 * Запись Manufacturer (write). Принимает и отдаёт ManufacturerData — Eloquent-модель наружу
 * не выходит (деталь Infrastructure, см. plan.md §3).
 */
interface ManufacturerCommandInterface
{
    /**
     * Создать производителя из import data.
     *
     * Шаги:
     * 1) Передать validated ManufacturerData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(ManufacturerData $data): ManufacturerData;

    /**
     * Обновить производителя по внешнему mfa_id.
     *
     * Шаги:
     * 1) Найти существующую запись по mfa_id.
     * 2) Применить значения ManufacturerData.
     * 3) Вернуть обновленный snapshot.
     */
    public function updateByMfaId(ManufacturerData $data): ManufacturerData;
}

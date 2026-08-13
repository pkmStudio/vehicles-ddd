<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface ManufacturerRepositoryInterface
{
    /**
     * Найти производителя по имени.
     *
     * Шаги:
     * 1) Выполнить read query по name.
     * 2) Вернуть ManufacturerData или null.
     */
    public function findByName(string $name): ?ManufacturerData;

    /**
     * Найти производителя по внешнему mfa_id.
     *
     * Шаги:
     * 1) Выполнить read query по mfa_id.
     * 2) Вернуть ManufacturerData или null.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData;

    /**
     * Производитель с минимальным mfa_id (для генерации отрицательных id новых марок).
     *
     * Шаги:
     * 1) Отсортировать производителей по mfa_id.
     * 2) Вернуть snapshot минимальной записи или null.
     */
    public function findMinMfaId(): ?ManufacturerData;
}

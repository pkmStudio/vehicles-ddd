<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability;

use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

/**
 * Описывает read port Warehouse kits для Applicability.
 */
interface WarehouseApplicabilityRepositoryInterface
{
    /**
     * Читает активные комплекты для расчета применяемости.
     *
     * Шаги:
     * 1. Принять optional id комплекта и размер chunk.
     * 2. Вернуть iterable DTO активных комплектов.
     *
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable;

    /**
     * Проверяет существование комплекта.
     *
     * Шаги:
     * 1. Принять внутренний id комплекта.
     * 2. Вернуть boolean-флаг существования записи.
     */
    public function kitExists(int $kitId): bool;
}

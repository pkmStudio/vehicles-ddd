<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Contracts\Clients;

use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

interface WarehouseApplicabilityClientInterface
{
    /**
     * Возвращает активные Warehouse kits для расчёта применяемости.
     * Шаги:
     * 1) Принять optional kitId для точечного расчёта и chunk для потокового чтения.
     * 2) Читать только активные kits с данными, нужными Applicability context.
     * 3) Вернуть iterable DTO без передачи Eloquent-моделей наружу.
     *
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeApplicabilityKits(?int $kitId = null, int $chunk = 1000): iterable;

    /**
     * Проверяет существование Warehouse kit по id.
     * Шаги:
     * 1) Принять id набора из контекста применяемости.
     * 2) Выполнить lightweight existence check во владельце Warehouse catalog.
     * 3) Вернуть boolean результат без загрузки полного DTO.
     */
    public function kitExists(int $kitId): bool;
}

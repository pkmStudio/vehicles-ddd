<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Clients;

interface WarehouseKitClientInterface
{
    /**
     * Проверяет существование комплекта в Warehouse boundary.
     *
     * Шаги:
     * 1. Передает kit id в read-only Warehouse client.
     * 2. Возвращает boolean existence result без чтения Eloquent-модели наружу.
     */
    public function exists(int $kitId): bool;
}

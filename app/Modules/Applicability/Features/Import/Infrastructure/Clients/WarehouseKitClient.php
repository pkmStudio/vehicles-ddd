<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;

/**
 * Адаптер публичного Warehouse API к import-порту Applicability.
 */
final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    /**
     * Получает публичный read-only client Warehouse.
     *
     * Шаги:
     * 1. Сохраняет module-level Warehouse applicability client.
     * 2. Оставляет проверку kit existence методу domain port-а.
     */
    public function __construct(
        private WarehouseApplicabilityClientInterface $warehouse,
    ) {}

    /**
     * Проверяет, что комплект существует в Warehouse перед записью применяемости.
     *
     * Шаги:
     * 1. Делегирует lookup публичному Warehouse client.
     * 2. Возвращает boolean result без раскрытия Warehouse model наружу.
     */
    public function exists(int $kitId): bool
    {
        return $this->warehouse->kitExists($kitId);
    }
}

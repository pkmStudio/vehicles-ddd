<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Domain\DTOs;

/**
 * DTO строки отчёта о несовпадении адаптеров в Warehouse-наборе дворников.
 */
final readonly class WiperAdapterAuditRowDTO
{
    /**
     * Хранит готовые значения колонок отчёта.
     */
    public function __construct(
        public int $kitId,
        public string $kit,
        public string $mismatchedAdapters,
        public string $place,
    ) {}
}

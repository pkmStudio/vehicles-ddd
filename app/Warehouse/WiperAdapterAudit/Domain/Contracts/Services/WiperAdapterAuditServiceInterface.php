<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Domain\Contracts\Services;

use App\Warehouse\WiperAdapterAudit\Domain\DTOs\WiperAdapterAuditRowDTO;
use Illuminate\Support\Collection;

/**
 * Порт расчёта строк отчёта о несовпадении адаптеров дворников.
 */
interface WiperAdapterAuditServiceInterface
{
    /**
     * Возвращает готовые строки отчёта.
     *
     * @return Collection<int, WiperAdapterAuditRowDTO>
     */
    public function rows(): Collection;
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\WiperAdapterAudit\WiperAdapterAuditExportRowDTO;
use Illuminate\Support\Collection;

/**
 * Локальный порт Export-фичи для получения строк аудита адаптеров дворников.
 */
interface WiperAdapterAuditClientInterface
{
    /**
     * Возвращает строки отчёта аудита адаптеров.
     *
     * @return Collection<int, WiperAdapterAuditExportRowDTO>
     */
    public function rows(): Collection;
}

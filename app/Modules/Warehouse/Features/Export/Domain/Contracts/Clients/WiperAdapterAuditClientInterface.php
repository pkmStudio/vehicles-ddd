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
     * Шаги:
     * 1) Запросить рассчитанные строки у owner-фичи аудита.
     * 2) Перевести строки в DTO Export-фичи.
     * 3) Вернуть коллекцию для Excel-адаптера.
     *
     * @return Collection<int, WiperAdapterAuditExportRowDTO>
     */
    public function rows(): Collection;
}

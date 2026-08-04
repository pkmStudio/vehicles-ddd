<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Clients;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\WiperAdapterAuditClientInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\WiperAdapterAudit\WiperAdapterAuditExportRowDTO;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\DTOs\WiperAdapterAuditRowDTO;
use Illuminate\Support\Collection;

/**
 * Адаптер Export → WiperAdapterAudit.
 */
final readonly class WiperAdapterAuditClient implements WiperAdapterAuditClientInterface
{
    /**
     * Получает сервис расчёта строк отчёта WiperAdapterAudit.
     */
    public function __construct(
        private WiperAdapterAuditServiceInterface $audit,
    ) {}

    /**
     * Возвращает строки отчёта аудита адаптеров.
     *
     * @return Collection<int, WiperAdapterAuditExportRowDTO>
     */
    public function rows(): Collection
    {
        return $this->audit->rows()
            ->map(fn (WiperAdapterAuditRowDTO $row): WiperAdapterAuditExportRowDTO => new WiperAdapterAuditExportRowDTO(
                kitId: $row->kitId,
                kit: $row->kit,
                mismatchedAdapters: $row->mismatchedAdapters,
                place: $row->place,
            ));
    }
}

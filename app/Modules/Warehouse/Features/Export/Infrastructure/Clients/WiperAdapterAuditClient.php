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
     *
     * Шаги:
     * 1) Принять сервис фичи WiperAdapterAudit через локальный adapter boundary.
     * 2) Сохранить сервис для чтения строк отчёта при экспорте.
     */
    public function __construct(
        private WiperAdapterAuditServiceInterface $audit,
    ) {}

    /**
     * Возвращает строки отчёта аудита адаптеров.
     *
     * Шаги:
     * 1) Получить строки аудита из owner-фичи.
     * 2) Преобразовать каждую строку во внутренний DTO Export-фичи.
     * 3) Вернуть коллекцию, пригодную для Excel-адаптера.
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

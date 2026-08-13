<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Jobs;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PkmStudio\MoySkladClient\Jobs\Middleware\MoySkladJobMiddleware;

/**
 * Queue job удаления товара МойСклад после локального удаления Warehouse-номенклатуры.
 */
final class DeleteNomenclatureJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60];

    /**
     * Сохраняет локальный и внешний контекст удаляемой номенклатуры.
     * Шаги:
     * 1) Сохранить local nomenclature id, part number и optional external identifiers как serializable state.
     * 2) Прочитать имя очереди из warehouse.moysklad config.
     * 3) Назначить job queue с fallback moysklad.
     */
    public function __construct(
        private readonly int $nomenclatureId,
        private readonly string $partNumber,
        private readonly ?string $externalId = null,
        private readonly ?int $integrationId = null,
    ) {
        $this->queue = (string) config('warehouse.moysklad.queue', 'moysklad');
    }

    /**
     * Возвращает middleware пакета МойСклад для circuit-breaker/rate-limit ошибок.
     * Шаги:
     * 1) Создать MoySkladJobMiddleware из внешнего пакета.
     * 2) Вернуть middleware списком для Laravel queue worker.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new MoySkladJobMiddleware];
    }

    /**
     * Делегирует удаление товара МойСклад application-сервису.
     * Шаги:
     * 1) Получить NomenclatureSyncServiceInterface из container при выполнении job.
     * 2) Передать local/external identifiers в delete().
     */
    public function handle(NomenclatureSyncServiceInterface $service): void
    {
        $service->delete(
            nomenclatureId: $this->nomenclatureId,
            partNumber: $this->partNumber,
            externalId: $this->externalId,
            integrationId: $this->integrationId,
        );
    }
}

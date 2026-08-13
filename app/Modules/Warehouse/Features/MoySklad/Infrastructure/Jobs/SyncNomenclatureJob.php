<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Jobs;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PkmStudio\MoySkladClient\Jobs\Middleware\MoySkladJobMiddleware;

/**
 * Queue job синхронизации одной Warehouse-номенклатуры с МойСклад.
 */
final class SyncNomenclatureJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60];

    /**
     * Сохраняет id номенклатуры и выбирает очередь МойСклад.
     * Шаги:
     * 1) Сохранить id номенклатуры как serializable scalar.
     * 2) Прочитать имя очереди из warehouse.moysklad config.
     * 3) Назначить job queue с fallback moysklad.
     */
    public function __construct(
        private readonly int $nomenclatureId,
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
     * Делегирует синхронизацию application-сервису.
     * Шаги:
     * 1) Получить NomenclatureSyncServiceInterface из container при выполнении job.
     * 2) Передать сохранённый nomenclatureId в sync().
     */
    public function handle(NomenclatureSyncServiceInterface $service): void
    {
        $service->sync($this->nomenclatureId);
    }
}

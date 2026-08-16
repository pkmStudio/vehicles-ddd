<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Jobs;

use App\Modules\Warehouse\Features\MoySklad\Application\Services\NomenclatureBackfillService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PkmStudio\MoySkladClient\Jobs\Middleware\MoySkladJobMiddleware;

/**
 * Queue job массового backfill связей Warehouse-номенклатуры с МойСклад.
 */
final class BackfillNomenclatureIntegrationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60];

    /**
     * Сохраняет размер чанка backfill и выбирает очередь МойСклад.
     * Шаги:
     * 1) Сохранить chunk как serializable scalar.
     * 2) Прочитать имя очереди из warehouse.moysklad config.
     * 3) Назначить job queue с fallback moysklad.
     */
    public function __construct(
        private readonly int $chunk = 100,
    ) {
        $this->queue = (string) config('warehouse.moysklad.queue', 'moysklad');
    }

    /**
     * Возвращает middleware пакета МойСклад для circuit-breaker/rate-limit ошибок.
     * Шаги:
     * 1) Создать MoySkladJobMiddleware из внешнего пакета.
     * 2) Вернуть middleware списком для Laravel queue worker.
     *
     * @return array<int, MoySkladJobMiddleware>
     */
    public function middleware(): array
    {
        return [new MoySkladJobMiddleware];
    }

    /**
     * Запускает backfill через application-сервис.
     * Шаги:
     * 1) Получить NomenclatureBackfillService из container при выполнении job.
     * 2) Передать сохранённый chunk в execute().
     */
    public function handle(NomenclatureBackfillService $service): void
    {
        $service->execute($this->chunk);
    }
}

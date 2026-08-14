<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\UseCases\External;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use Throwable;

/**
 * Запускает Warehouse-импорт по внешнему RabbitMQ-запросу. В отличие от `Warehouse\Export`,
 * уведомление о результате сюда не входит — импорт асинхронный (ShouldQueue), реальное завершение
 * наступает позже, на `AfterImport` (см. Listeners).
 */
final readonly class StartExternalFileImportUseCase
{
    /**
     * Получает сервис идемпотентности и фабрику Excel-адаптеров импорта.
     *
     * Шаги:
     * 1) Принять cache-порт идемпотентности внешнего запуска.
     * 2) Принять фабрику выбора Excel-адаптера по типу импорта.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ImportFileFactoryInterface $importFactory,
    ) {}

    /**
     * Этот метод принимает внешний запрос и диспатчит Excel-импорт в очередь.
     *
     * Шаги:
     * 1) Проверить operationId через cache — повтор сообщения брокера не должен запустить импорт дважды.
     * 2) Запомнить disk+path файла для отложенного удаления после завершения.
     * 3) Выбрать адаптер импорта по типу запроса и передать ему путь и контекст прогона.
     * 4) На ошибке снять cache-флаг принятого operationId и пробросить исключение.
     */
    public function execute(ExternalImportFileRequestDTO $request): void
    {
        $runAccepted = $this->cache->accept($request->operationId);

        if (! $runAccepted) {
            return;
        }

        try {
            if ($request->cleanupAfterImport) {
                $this->cache->rememberCleanup($request);
            }

            $context = new ImportRunContextDTO(
                userId: $request->userId,
                operationId: $request->operationId,
            );

            $this->importFactory
                ->make($request->importType)
                ->import(
                    path: $request->path,
                    context: $context,
                    disk: $request->disk,
                );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);

            throw $e;
        }
    }
}

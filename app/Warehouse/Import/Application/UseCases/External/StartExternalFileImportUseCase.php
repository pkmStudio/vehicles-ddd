<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\UseCases\External;

use App\Warehouse\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Warehouse\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Warehouse\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Warehouse\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use Throwable;

/**
 * Запускает Warehouse-импорт по внешнему RabbitMQ-запросу. В отличие от `Warehouse\Export`,
 * уведомление о результате сюда не входит — импорт асинхронный (ShouldQueue), реальное завершение
 * наступает позже, на `AfterImport` (см. Listeners).
 */
final readonly class StartExternalFileImportUseCase implements StartExternalFileImportUseCaseInterface
{
    /**
     * Получает сервис идемпотентности и фабрику Excel-адаптеров импорта.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ImportFileFactoryInterface $importFactory,
    ) {}

    /**
     * Этот метод принимает внешний запрос и диспатчит Excel-импорт в очередь.
     *
     * Шаги:
     * 1) Проверить runId через cache — повтор сообщения брокера не должен запустить импорт дважды.
     * 2) Запомнить disk+path файла для отложенного удаления после завершения.
     * 3) Выбрать адаптер импорта по типу запроса и передать ему путь и контекст прогона.
     * 4) На ошибке снять cache-флаг принятого runId и пробросить исключение.
     */
    public function execute(ExternalImportFileRequestDTO $request): void
    {
        if (! $this->cache->accept($request->runId)) {
            return;
        }

        $this->cache->rememberCleanup($request);

        try {
            $context = new ImportRunContextDTO(
                userId: $request->userId,
                runId: $request->runId,
            );

            $this->importFactory
                ->make($request->importType)
                ->import(
                    path: $request->path,
                    context: $context,
                    disk: $request->disk,
                );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);

            throw $e;
        }
    }
}

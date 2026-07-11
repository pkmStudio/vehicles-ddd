<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\UseCases\External;

use App\Vehicles\Import\Domain\Contracts\Factories\ExternalFileImportFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Vehicles\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Vehicles\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Vehicles\Import\Domain\DTOs\ImportRunContextDTO;
use Throwable;

/**
 * Принимает RabbitMQ-команду на импорт файла и запускает существующий импортный адаптер.
 */
final readonly class StartExternalFileImportUseCase implements StartExternalFileImportUseCaseInterface
{
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ExternalFileImportFactoryInterface $importFactory,
    ) {}

    /**
     * Обеспечивает идемпотентность runId и запускает выбранный импорт.
     *
     * Шаги:
     * 1. Просит cache-сервис принять runId; повторный запрос не запускает импорт.
     * 2. Сохраняет cleanup-инструкцию, чтобы после завершения импорта удалить исходный файл.
     * 3. Создаёт контекст запуска с userId/runId и выбирает импортный адаптер через фабрику.
     * 4. Передаёт path и disk в импортный адаптер; Laravel Excel сам читает файл из указанного disk.
     * 5. При ошибке снимает отметку принятого runId, чтобы запрос можно было повторить.
     * 6. Пробрасывает ошибку дальше, чтобы RabbitMQ-обработчик не подтвердил неуспешное сообщение как обработанное.
     */
    public function execute(ExternalImportFileRequestDTO $request): void
    {
        $isAccepted = $this->cache->accept($request);
        if (! $isAccepted) {
            return;
        }

        try {
            $this->cache->rememberCleanup($request);
            $context = new ImportRunContextDTO(userId: $request->userId, runId: $request->runId);
            $importService = $this->importFactory->make($request->importType);
            $importService->import($request->path, $context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);

            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\UseCases\External;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;
use Throwable;

final readonly class StartExternalFileImportUseCase implements StartExternalFileImportUseCaseInterface
{
    /**
     * Получает зависимости запуска внешнего import workflow.
     *
     * Шаги:
     * 1. Сохраняет cache port для idempotency и cleanup metadata.
     * 2. Сохраняет factory, выбирающую Excel import adapter по типу импорта.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ImportFileFactoryInterface $importFactory,
    ) {}

    /**
     * Запускает импорт внешнего файла применяемости.
     *
     * Шаги:
     * 1. Проверяет operation id через cache guard и завершает дубликат без повторного импорта.
     * 2. Если файл нужно удалить после импорта, сохраняет cleanup metadata до запуска Excel.
     * 3. Собирает run context для user id и operation id.
     * 4. Выбирает import adapter по типу файла и запускает импорт с path/disk.
     * 5. При ошибке снимает idempotency marker и пробрасывает исключение для retryable failure.
     */
    public function execute(ExternalImportFileRequestDTO $request): void
    {
        if (! $this->cache->accept($request->operationId)) {
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
                ->import($request->path, $context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);

            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Адаптер входящих RabbitMQ-событий импорта файла к use case импорта.
 */
final readonly class ImportFileRequestedHandler
{
    /**
     * Получить зависимости RabbitMQ handler-а import request.
     *
     * Шаги:
     * 1) Принять use case внешнего запуска импорта.
     * 2) Принять validator inbound payload.
     */
    public function __construct(
        private StartExternalFileImportUseCaseInterface $useCase,
        private ImportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Провалидировать payload, собрать DTO и передать его в сценарий запуска импорта.
     *
     * Шаги:
     * 1) Создать validator для inbound RabbitMQ payload.
     * 2) Залогировать invalid payload и завершить обработку без retryable exception.
     * 3) Собрать ExternalImportFileRequestDTO с disk fallback из config.
     * 4) Передать DTO в external import use case.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error('RabbitMQ: Import file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $filesDisk = (string) ($data['disk'] ?? config('filesystems.files_disk', 's3'));

        $request = new ExternalImportFileRequestDTO(
            userId: (int) $data['user_id'],
            operationId: (string) $data['operation_id'],
            importType: ExternalImportTypeEnum::from((string) $data['import_type']),
            disk: $filesDisk,
            path: (string) $data['path'],
            cleanupAfterImport: (bool) ($data['cleanup_after_import'] ?? true),
        );
        $this->useCase->execute($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Messaging\Handlers;

use App\Vehicles\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Vehicles\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Vehicles\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Vehicles\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Адаптер входящих RabbitMQ-событий импорта файла к use case импорта.
 */
final readonly class ImportFileRequestedHandler
{
    public function __construct(
        private StartExternalFileImportUseCaseInterface $useCase,
        private ImportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Провалидировать payload, собрать DTO и передать его в сценарий запуска импорта.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Import file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $filesDisk = (string) config('filesystems.files_disk', 's3');

        $this->useCase->execute(new ExternalImportFileRequestDTO(
            userId: (int) $data['user_id'],
            runId: (string) $data['run_id'],
            importType: ExternalImportTypeEnum::from((string) $data['import_type']),
            disk: $filesDisk,
            path: (string) $data['path'],
        ));
    }
}

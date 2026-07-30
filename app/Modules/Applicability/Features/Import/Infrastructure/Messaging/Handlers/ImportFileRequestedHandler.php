<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ImportFileRequestedHandler
{
    public function __construct(
        private StartExternalFileImportUseCaseInterface $useCase,
        private ImportFileRequestedPayloadValidator $validator,
    ) {}

    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Applicability import file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $this->useCase->execute(new ExternalImportFileRequestDTO(
            userId: (int) $data['user_id'],
            operationId: (string) $data['operation_id'],
            importType: ImportTypeEnum::from((string) $data['import_type']),
            disk: (string) config('filesystems.files_disk', 's3'),
            path: (string) $data['path'],
            cleanupAfterImport: (bool) ($data['cleanup_after_import'] ?? true),
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Validators\ExportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ExportFileRequestedHandler
{
    public function __construct(
        private StartExportUseCaseInterface $useCase,
        private ExportFileRequestedPayloadValidator $validator,
    ) {}

    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Applicability export file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $this->useCase->execute(new ExportFileRequestDTO(
            userId: (int) $data['user_id'],
            runId: (string) $data['run_id'],
            exportType: ExportTypeEnum::from((string) $data['export_type']),
            disk: (string) config('applicability.export.output.disk', 'local'),
        ));
    }
}

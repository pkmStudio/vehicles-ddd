<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Messaging\Validators\ExportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

/**
 * Адаптер входящих RabbitMQ-событий запроса на экспорт к use case экспорта.
 */
final readonly class ExportFileRequestedHandler
{
    public function __construct(
        private StartExportUseCaseInterface $useCase,
        private ExportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Провалидировать payload, собрать DTO и передать его в сценарий запуска экспорта.
     *
     * Диск для выходного файла — из собственного конфига (vehicles.export.output.disk),
     * не из сообщения: это наш выбор, куда писать, а не то, что просит инициатор
     * (симметрично тому, как ImportFileRequestedHandler резолвит disk для входного
     * файла из filesystems.files_disk, а не из payload).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);
        $validationFailed = $validator->fails();

        if ($validationFailed) {
            Log::error('RabbitMQ: Export file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $outputDisk = (string) config('vehicles.export.output.disk', 'local');

        $request = new ExportFileRequestDTO(
            userId: (int) $data['user_id'],
            runId: (string) $data['run_id'],
            exportType: ExportTypeEnum::from((string) $data['export_type']),
            isAllow: (bool) ($data['is_allow'] ?? false),
            disk: $outputDisk,
        );
        $this->useCase->execute($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;

/**
 * DTO для payload события завершения импорта, которое отправляется во внешние сервисы.
 */
final readonly class ImportCompletionNotificationDTO
{
    /**
     * Фиксирует payload уведомления о завершении import run.
     */
    public function __construct(
        public int $userId,
        public ImportCompletionStatusEnum $status,
        public ExternalImportTypeEnum $importType,
        public ?string $operationId = null,
        public ?string $disk = null,
        public int $errorsCount = 0,
        public ?string $path = null,
    ) {}

    /**
     * @return array{user_id: int, operation_id: ?string, status: string, import_type: string, disk: ?string, errors_count: int, path: ?string, failures_report_path: ?string, failures_report_disk: ?string}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'operation_id' => $this->operationId,
            'status' => $this->status->value,
            'import_type' => $this->importType->value,
            'disk' => $this->disk,
            'errors_count' => $this->errorsCount,
            'path' => $this->path,
            'failures_report_path' => $this->path,
            'failures_report_disk' => $this->disk,
        ];
    }
}

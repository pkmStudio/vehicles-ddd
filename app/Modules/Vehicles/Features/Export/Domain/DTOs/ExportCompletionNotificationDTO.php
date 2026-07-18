<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * DTO для payload события завершения экспорта, которое отправляется во внешние
 * сервисы (outbound FILE_EXPORTED, config/rabbit-transport.php). Симметрично
 * Import\Domain\DTOs\ImportCompletionNotificationDTO — публикуется на любой исход
 * (Completed и Failed), не только на успех; `path` есть только при Completed.
 */
final readonly class ExportCompletionNotificationDTO
{
    public function __construct(
        public int $userId,
        public ExportCompletionStatusEnum $status,
        public ExportTypeEnum $exportType,
        public ?string $runId = null,
        public ?string $disk = null,
        public ?string $path = null,
    ) {}

    /**
     * @return array{user_id: int, status: string, export_type: string, run_id: ?string, disk: ?string, path: ?string}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status->value,
            'export_type' => $this->exportType->value,
            'run_id' => $this->runId,
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }
}

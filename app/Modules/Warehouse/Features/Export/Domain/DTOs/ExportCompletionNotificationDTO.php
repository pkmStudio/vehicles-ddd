<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\DTOs;

use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Payload события завершения Warehouse-экспорта.
 */
final readonly class ExportCompletionNotificationDTO
{
    /**
     * Хранит wire-данные уведомления о результате Warehouse-экспорта.
     */
    public function __construct(
        public int $userId,
        public ExportCompletionStatusEnum $status,
        public ExportTypeEnum $exportType,
        public ?string $runId = null,
        public ?string $disk = null,
        public ?string $path = null,
        public ?int $typeId = null,
    ) {}

    /**
     * Преобразует DTO в snake_case payload для rabbit-transport.
     *
     * @return array{user_id: int, status: string, export_type: string, run_id: ?string, disk: ?string, path: ?string, type_id: ?int}
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
            'type_id' => $this->typeId,
        ];
    }
}

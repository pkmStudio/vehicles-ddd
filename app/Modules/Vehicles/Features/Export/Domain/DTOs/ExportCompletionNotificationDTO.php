<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * DTO для payload события завершения экспорта, которое отправляется во внешние
 * сервисы (outbound VEHICLES_FILE_EXPORTED, config/rabbit-transport.php). Симметрично
 * Import\Domain\DTOs\ImportCompletionNotificationDTO — публикуется на любой исход
 * (Completed и Failed), не только на успех; `path` есть только при Completed.
 */
final readonly class ExportCompletionNotificationDTO
{
    /**
     * @param  int  $userId  внешний идентификатор инициатора export request
     * @param  ExportCompletionStatusEnum  $status  итоговый статус export flow
     * @param  ExportTypeEnum  $exportType  тип выгруженного каталога
     * @param  string|null  $operationId  корреляционный id внешнего запроса
     * @param  string|null  $disk  storage disk с export-файлом
     * @param  string|null  $path  путь к файлу, заполнен только при успешном экспорте
     */
    public function __construct(
        public int $userId,
        public ExportCompletionStatusEnum $status,
        public ExportTypeEnum $exportType,
        public ?string $operationId = null,
        public ?string $disk = null,
        public ?string $path = null,
    ) {}

    /**
     * @return array{user_id: int, status: string, export_type: string, operation_id: ?string, disk: ?string, path: ?string}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status->value,
            'export_type' => $this->exportType->value,
            'operation_id' => $this->operationId,
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }
}

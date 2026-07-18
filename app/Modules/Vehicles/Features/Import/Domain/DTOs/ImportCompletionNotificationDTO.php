<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;

/**
 * DTO для payload события завершения импорта, которое отправляется во внешние сервисы.
 */
final readonly class ImportCompletionNotificationDTO
{
    public function __construct(
        public int $userId,
        public ImportCompletionStatusEnum $status,
        public ?string $runId = null,
        public int $errorsCount = 0,
        public ?string $path = null,
    ) {
    }

    /**
     * @return array{user_id: int, run_id: ?string, status: string, errors_count: int, path: ?string}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'run_id' => $this->runId,
            'status' => $this->status->value,
            'errors_count' => $this->errorsCount,
            'path' => $this->path,
        ];
    }
}


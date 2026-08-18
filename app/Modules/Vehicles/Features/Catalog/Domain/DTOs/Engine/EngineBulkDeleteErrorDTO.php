<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

/**
 * Описывает одну ошибку строки при массовом удалении двигателей.
 */
final readonly class EngineBulkDeleteErrorDTO
{
    /**
     * Получает id записи, machine-readable причину и опциональный бизнес-ключ.
     */
    public function __construct(
        public ?int $id,
        public string $reason,
        public ?string $businessKey = null,
    ) {}
}

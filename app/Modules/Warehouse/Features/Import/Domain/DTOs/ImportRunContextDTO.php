<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs;

/**
 * Контекст запуска Warehouse-импорта. `userId` нужен nullable (в отличие от Vehicles) — тот же
 * порт вызывается и из тонкой Artisan-команды (оператор в терминале, реального userId нет), и из
 * RabbitMQ-триггера (userId приходит в payload).
 */
final readonly class ImportRunContextDTO
{
    /**
     * Хранит контекст инициатора и уникального прогона импорта.
     */
    public function __construct(
        public ?int $userId,
        public string $runId,
    ) {}
}

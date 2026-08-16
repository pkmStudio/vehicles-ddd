<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

/**
 * Команда на bulk-сброс Warehouse-наборов из внешнего RabbitMQ-запроса.
 */
final readonly class KitResetRequestDTO
{
    /**
     * @param  int  $userId  Пользователь CRM, запросивший операцию.
     * @param  string  $operationId  Идентификатор операции для корреляции результата.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
    ) {}
}

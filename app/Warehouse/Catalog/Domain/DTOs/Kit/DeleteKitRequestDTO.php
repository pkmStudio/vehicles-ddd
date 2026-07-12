<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Kit;

/**
 * DTO входящей команды на удаление Warehouse-набора.
 */
final readonly class DeleteKitRequestDTO
{
    /**
     * Хранит id удаляемого набора и контекст операции.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
    ) {}
}

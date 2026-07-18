<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

/**
 * DTO входящей команды на удаление Warehouse-бренда.
 */
final readonly class DeleteBrandRequestDTO
{
    /**
     * Хранит id удаляемого бренда и контекст операции.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
    ) {}
}

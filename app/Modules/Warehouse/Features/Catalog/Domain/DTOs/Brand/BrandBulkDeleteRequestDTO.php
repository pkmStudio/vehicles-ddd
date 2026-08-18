<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

/**
 * DTO входящей команды на массовое удаление брендов.
 */
final readonly class BrandBulkDeleteRequestDTO
{
    /**
     * Получает автора операции, correlation id и список внутренних id брендов.
     *
     * @param  list<int>  $ids
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $ids,
    ) {}
}

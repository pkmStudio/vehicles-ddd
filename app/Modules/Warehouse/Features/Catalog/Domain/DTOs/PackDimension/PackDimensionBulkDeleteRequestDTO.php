<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension;

/**
 * DTO входящей команды на массовое удаление упаковок.
 */
final readonly class PackDimensionBulkDeleteRequestDTO
{
    /**
     * Получает автора операции, correlation id и список внутренних id упаковок.
     *
     * @param  list<int>  $ids
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $ids,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

/**
 * DTO входящей команды на обновление Warehouse-набора.
 */
final readonly class UpdateKitRequestDTO
{
    /**
     * @param  array<int, int>  $nomenclatureIds
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
        public array $nomenclatureIds,
        public bool $isSaleSeparately = false,
        public bool $isActive = true,
        public int $guarantee = 12,
    ) {}
}

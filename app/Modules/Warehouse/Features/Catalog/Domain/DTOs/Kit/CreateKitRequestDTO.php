<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

/**
 * DTO входящей команды на создание Warehouse-набора.
 */
final readonly class CreateKitRequestDTO
{
    /**
     * @param  array<int, int>  $nomenclatureIds
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $nomenclatureIds,
        public int $guarantee,
        public bool $isSaleSeparately = false,
        public bool $isActive = true,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

/**
 * DTO входящей команды на обновление Warehouse-бренда.
 */
final readonly class UpdateBrandRequestDTO
{
    /**
     * Хранит валидированные поля обновления бренда.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
        public string $name,
        public string $numberSert,
        public string $dateStart,
        public string $dateEnd,
        public string $char,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Brand;

/**
 * DTO входящей команды на создание Warehouse-бренда.
 */
final readonly class CreateBrandRequestDTO
{
    /**
     * Хранит валидированные поля создания бренда.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public string $name,
        public string $numberSert,
        public string $dateStart,
        public string $dateEnd,
        public ?string $char = null,
    ) {}
}

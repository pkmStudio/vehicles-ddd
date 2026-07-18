<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Brand;

/**
 * Доменный факт создания Warehouse-бренда.
 */
final readonly class BrandCreated
{
    /**
     * Хранит контекст операции и созданный бренд.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $brand,
    ) {}
}

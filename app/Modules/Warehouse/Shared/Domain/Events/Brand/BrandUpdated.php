<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Brand;

/**
 * Доменный факт обновления Warehouse-бренда.
 */
final readonly class BrandUpdated
{
    /**
     * Хранит контекст операции и обновлённый бренд.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $brand,
    ) {}
}

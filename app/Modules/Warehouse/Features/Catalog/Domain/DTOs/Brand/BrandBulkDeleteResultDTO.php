<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Результат массового удаления брендов.
 */
final readonly class BrandBulkDeleteResultDTO
{
    /**
     * Получает counters операции и typed errors по строкам.
     *
     * @param  list<BrandBulkDeleteErrorDTO>  $errors
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public WarehouseCatalogEntityEnum $entity,
        public WarehouseCatalogMutationStatusEnum $status,
        public int $requested,
        public int $deleted,
        public int $skipped,
        public int $failed,
        public array $errors = [],
    ) {}
}

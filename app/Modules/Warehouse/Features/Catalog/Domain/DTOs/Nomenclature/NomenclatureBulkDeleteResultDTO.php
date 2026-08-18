<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Результат массового удаления номенклатуры.
 */
final readonly class NomenclatureBulkDeleteResultDTO
{
    /**
     * Получает counters операции и typed errors по строкам.
     *
     * @param  list<NomenclatureBulkDeleteErrorDTO>  $errors
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

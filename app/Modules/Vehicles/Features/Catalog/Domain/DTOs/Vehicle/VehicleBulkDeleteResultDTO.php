<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;

/**
 * Результат массового удаления автомобилей.
 */
final readonly class VehicleBulkDeleteResultDTO
{
    /**
     * Получает counters операции и typed errors по строкам.
     *
     * @param  list<VehicleBulkDeleteErrorDTO>  $errors
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public CatalogEntityEnum $entity,
        public CatalogMutationStatusEnum $status,
        public int $requested,
        public int $deleted,
        public int $skipped,
        public int $failed,
        public array $errors = [],
    ) {}
}

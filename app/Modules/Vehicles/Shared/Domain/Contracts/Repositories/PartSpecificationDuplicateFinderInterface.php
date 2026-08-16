<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\PartSpecificationWritePolicyResultDTO;

/**
 * Ищет конфликтующую part specification по module-level natural-key.
 */
interface PartSpecificationDuplicateFinderInterface
{
    /**
     * Возвращает id конфликтующей записи или null, если owner/template/details свободны.
     */
    public function findDuplicate(
        PartSpecificationWritePolicyResultDTO $specification,
    ): ?int;
}

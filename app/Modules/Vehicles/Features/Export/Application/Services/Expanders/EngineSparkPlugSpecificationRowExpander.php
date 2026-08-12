<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\Expanders;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders\EngineSparkPlugSpecificationRowExpanderInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Разворачивает двигатели со спецификациями свечей зажигания в плоские строки экспорта.
 */
final readonly class EngineSparkPlugSpecificationRowExpander implements EngineSparkPlugSpecificationRowExpanderInterface
{
    /**
     * @param  Collection<int, EngineData>  $entities  модели с загруженной связью partSpecifications
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function expand(Collection $entities): Collection
    {
        $rows = collect();

        foreach ($entities as $entity) {
            $partSpecificationsEmpty = $entity->partSpecifications->isEmpty();

            if ($partSpecificationsEmpty) {
                $emptyRow = new PartSpecificationExportRowDTO(entity: $entity, specification: null);
                $rows->push($emptyRow);

                continue;
            }

            foreach ($entity->partSpecifications as $specification) {
                $row = new PartSpecificationExportRowDTO(entity: $entity, specification: $specification);
                $rows->push($row);
            }
        }

        return $rows;
    }
}

<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\Expanders;

use App\Vehicles\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Vehicles\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Разворачивает сущности со связью partSpecifications в плоские строки экспорта:
 * по одной строке на каждую спецификацию, либо одну строку с null, если их нет.
 * Используется листами экспорта, где деталей может быть несколько на сущность.
 */
final readonly class PartSpecificationRowExpander implements PartSpecificationRowExpanderInterface
{
    /**
     * @param  Collection<int, EngineData>  $entities  модели с загруженной связью partSpecifications
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function expand(Collection $entities): Collection
    {
        $rows = collect();

        foreach ($entities as $entity) {
            if ($entity->partSpecifications->isEmpty()) {
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

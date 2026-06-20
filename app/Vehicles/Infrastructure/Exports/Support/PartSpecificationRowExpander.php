<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Support;

use Illuminate\Support\Collection;

/**
 * Разворачивает сущности со связью partSpecifications в плоские строки экспорта:
 * по одной строке на каждую спецификацию, либо одну строку с null, если их нет.
 * Используется листами экспорта, где деталей может быть несколько на сущность.
 */
final readonly class PartSpecificationRowExpander
{
    /**
     * @param  Collection<int, object>  $entities  модели с загруженной связью partSpecifications
     * @return Collection<int, object{entity: object, specification: object|null}>
     */
    public function expand(Collection $entities): Collection
    {
        $rows = collect();

        foreach ($entities as $entity) {
            if ($entity->partSpecifications->isEmpty()) {
                $rows->push((object) ['entity' => $entity, 'specification' => null]);

                continue;
            }

            foreach ($entity->partSpecifications as $specification) {
                $rows->push((object) ['entity' => $entity, 'specification' => $specification]);
            }
        }

        return $rows;
    }
}

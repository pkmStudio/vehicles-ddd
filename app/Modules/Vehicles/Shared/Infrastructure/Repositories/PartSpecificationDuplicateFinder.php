<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Infrastructure\Repositories;

use App\Modules\Vehicles\Shared\Domain\Contracts\Repositories\PartSpecificationDuplicateFinderInterface;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\PartSpecificationWritePolicyResultDTO;
use Illuminate\Support\Facades\DB;

/**
 * Ищет дубли part_specifications на уровне общей таблицы Vehicles.
 */
final readonly class PartSpecificationDuplicateFinder implements PartSpecificationDuplicateFinderInterface
{
    /**
     * Ищет дубль по owner/template/details без зависимости от feature-local Eloquent-моделей.
     */
    public function findDuplicate(
        PartSpecificationWritePolicyResultDTO $specification,
    ): ?int {
        $query = DB::table('part_specifications')
            ->select(['id'])
            ->where('partable_type', $specification->partableType->value)
            ->where('partable_id', $specification->partableId)
            ->where('template', $specification->template->value)
            ->whereRaw('details = CAST(? AS jsonb)', [
                json_encode($specification->details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->orderBy('id');

        if ($specification->id !== null) {
            $query->where('id', '<>', $specification->id);
        }

        $id = $query->value('id');

        return $id === null ? null : (int) $id;
    }
}

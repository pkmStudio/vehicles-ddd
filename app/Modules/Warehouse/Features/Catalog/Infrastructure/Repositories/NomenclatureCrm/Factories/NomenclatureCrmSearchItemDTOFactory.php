<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;

/**
 * Builds CRM search item DTO from SQL nomenclature projection.
 */
final readonly class NomenclatureCrmSearchItemDTOFactory
{
    public function make(object $nomenclature): NomenclatureCrmSearchItemDTO
    {
        return new NomenclatureCrmSearchItemDTO(
            id: (int) $nomenclature->id,
            label: $this->label($nomenclature),
            partNumber: (string) $nomenclature->part_number,
        );
    }

    private function label(object $nomenclature): string
    {
        return trim(sprintf(
            '%s | %s | %s | %s',
            $nomenclature->id,
            $nomenclature->part_number,
            $nomenclature->brand_name,
            $nomenclature->name,
        ));
    }
}

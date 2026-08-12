<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;

/**
 * Собирает CRM search item DTO из SQL-проекции номенклатуры.
 */
final readonly class NomenclatureCrmSearchItemDTOFactory
{
    /**
     * Собирает search item DTO для autocomplete номенклатур.
     *
     * Шаги:
     * 1) Считать id, part_number, brand_name и name из search projection.
     * 2) Собрать человекочитаемый label для autocomplete.
     * 3) Вернуть NomenclatureCrmSearchItemDTO.
     */
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

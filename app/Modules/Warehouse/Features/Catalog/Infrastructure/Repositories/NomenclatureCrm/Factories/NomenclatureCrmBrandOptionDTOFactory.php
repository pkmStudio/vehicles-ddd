<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;

/**
 * Builds CRM brand option DTO from SQL brand projection.
 */
final readonly class NomenclatureCrmBrandOptionDTOFactory
{
    public function make(object $brand): NomenclatureCrmOptionDTO
    {
        return new NomenclatureCrmOptionDTO(
            id: (int) $brand->id,
            label: (string) $brand->name,
            meta: [
                'char' => isset($brand->char) ? (string) $brand->char : null,
            ],
        );
    }
}

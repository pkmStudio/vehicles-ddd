<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;

/**
 * Собирает CRM option DTO бренда из SQL-проекции.
 */
final readonly class NomenclatureCrmBrandOptionDTOFactory
{
    /**
     * Собирает option DTO бренда для фильтров CRM.
     *
     * Шаги:
     * 1) Считать id, name и char из brand projection.
     * 2) Перенести char в meta для фронтового фильтра.
     * 3) Вернуть NomenclatureCrmOptionDTO.
     */
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

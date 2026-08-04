<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;

/**
 * Builds CRM type option DTO from SQL type projection.
 */
final readonly class NomenclatureCrmTypeOptionDTOFactory
{
    public function __construct(
        private NomenclatureCrmTypeTemplateResolver $templateResolver,
    ) {}

    public function make(object $type): NomenclatureCrmOptionDTO
    {
        return new NomenclatureCrmOptionDTO(
            id: (int) $type->id,
            label: (string) $type->name,
            meta: [
                'char' => isset($type->char) ? (string) $type->char : null,
                'template' => $this->templateResolver->value($type),
            ],
        );
    }
}

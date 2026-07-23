<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Spatie\LaravelData\Data;

final class KitData extends Data
{
    /**
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function __construct(
        public readonly int $id,
        public readonly int $typeId,
        public readonly int $quantityInPackage,
        public readonly bool $isActive,
        public readonly array $nomenclatures = [],
        public readonly ?TypeData $type = null,
        public readonly ?NomenclatureDetailTemplateEnum $template = null,
    ) {}
}

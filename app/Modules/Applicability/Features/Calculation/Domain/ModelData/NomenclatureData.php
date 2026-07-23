<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Spatie\LaravelData\Data;

final class NomenclatureData extends Data
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $typeId,
        public readonly int $quantityInPak,
        public readonly array $details,
        public readonly ?int $id = null,
        public readonly ?int $sort = null,
        public readonly ?TypeData $type = null,
        public readonly ?NomenclatureDetailTemplateEnum $template = null,
    ) {}
}

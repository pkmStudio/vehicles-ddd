<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Spatie\LaravelData\Data;

final class TypeData extends Data
{
    /**
     * Создает снимок типа Warehouse-номенклатуры для расчета.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $char = null,
        public readonly ?int $id = null,
        public readonly ?NomenclatureDetailTemplateEnum $template = null,
    ) {}
}

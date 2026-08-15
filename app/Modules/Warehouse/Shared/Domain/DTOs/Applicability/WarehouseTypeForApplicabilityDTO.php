<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Applicability;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use Spatie\LaravelData\Data;

final class WarehouseTypeForApplicabilityDTO extends Data
{
    /**
     * Передаёт Applicability context публичный снимок warehouse type.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $char,
        public readonly ?NomenclatureDetailTemplateEnum $template = null,
    ) {}
}

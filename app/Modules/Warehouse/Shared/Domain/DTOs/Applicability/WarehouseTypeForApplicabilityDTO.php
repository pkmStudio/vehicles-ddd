<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Applicability;

use Spatie\LaravelData\Data;

final class WarehouseTypeForApplicabilityDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $char = null,
    ) {}
}

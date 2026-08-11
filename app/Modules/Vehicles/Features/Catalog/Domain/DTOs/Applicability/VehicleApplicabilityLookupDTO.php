<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability;

final readonly class VehicleApplicabilityLookupDTO
{
    public function __construct(
        public int $id,
        public int $msId,
        public ?int $parentId,
    ) {}
}

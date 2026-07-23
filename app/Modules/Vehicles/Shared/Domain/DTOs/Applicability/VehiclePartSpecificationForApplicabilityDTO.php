<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Applicability;

use Spatie\LaravelData\Data;

final class VehiclePartSpecificationForApplicabilityDTO extends Data
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $id,
        public readonly int $vehicleId,
        public readonly array $details,
    ) {}
}

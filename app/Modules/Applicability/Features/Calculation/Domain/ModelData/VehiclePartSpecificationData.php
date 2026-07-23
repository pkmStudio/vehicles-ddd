<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Spatie\LaravelData\Data;

final class VehiclePartSpecificationData extends Data
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $id,
        public readonly int $vehicleId,
        public readonly DetailTemplateEnum $template,
        public readonly array $details,
    ) {}
}

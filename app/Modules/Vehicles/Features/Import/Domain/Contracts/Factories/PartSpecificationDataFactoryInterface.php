<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface PartSpecificationDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function make(int $engineId, array $details): PartSpecificationData;
}

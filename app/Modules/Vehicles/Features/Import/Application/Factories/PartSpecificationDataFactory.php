<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class PartSpecificationDataFactory implements PartSpecificationDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function make(int $engineId, array $details): PartSpecificationData
    {
        return new PartSpecificationData(
            partableType: PartableTypeEnum::ENGINE->value,
            partableId: $engineId,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
        );
    }
}

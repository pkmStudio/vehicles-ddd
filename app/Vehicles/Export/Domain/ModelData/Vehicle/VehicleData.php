<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\ModelData\Vehicle;

use App\Vehicles\Export\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Export\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class VehicleData extends Data
{
    /**
     * @param  ManufacturerData|null  $manufacturer  заполняется Repository::forMainSheet()/forWiperSheet()
     * @param  self|null  $parent  заполняется Repository::forMainSheet()/forWiperSheet() (один уровень, без внуков)
     * @param  Collection<int, PartSpecificationData>|null  $partSpecifications  заполняется только
     *                                                       Repository::forWiperSheet() (шаблон wiper, с featureValue)
     */
    public function __construct(
        public readonly int $msId,
        public readonly int $mfaId,
        public readonly int $manufacturerId,
        public readonly string $name,
        public readonly VehicleTypeEnum $type,
        public readonly SteeringTypeEnum $steeringType,
        public readonly ?string $generation = null,
        public readonly ?CarcaseTypeEnum $typeCarcase = null,
        public readonly ?int $generationYearFrom = null,
        public readonly ?int $generationYearTo = null,
        public readonly string $provider = 'TD',
        public readonly ?int $parentId = null,
        public readonly ?string $excelTableId = null,
        public readonly ?string $localizedName = null,
        public readonly ?string $generationShort = null,
        public readonly bool $isAllow = false,
        public readonly ?int $id = null,
        public readonly ?ManufacturerData $manufacturer = null,
        public readonly ?self $parent = null,
        public readonly ?Collection $partSpecifications = null,
    ) {}
}

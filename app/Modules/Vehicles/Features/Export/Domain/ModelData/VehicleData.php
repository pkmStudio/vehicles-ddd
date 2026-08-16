<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class VehicleData extends Data
{
    /**
     * @param  ManufacturerData|null  $manufacturer  заполняется Repository::forSheet()
     * @param  self|null  $parent  заполняется Repository::forSheet() (один уровень, без внуков)
     * @param  Collection<int, PartSpecificationData>|null  $partSpecifications  заполняется только
     *                                                                           Repository::forSheet(VehicleExportSheetEnum::Wiper)
     *                                                                           (шаблон wiper, с featureValue)
     */
    public function __construct(
        public readonly int $msId,
        public readonly int $mfaId,
        public readonly int $manufacturerId,
        public readonly string $name,
        public readonly VehicleTypeEnum $type,
        public readonly SteeringTypeEnum $steeringType,
        public readonly CarcaseTypeEnum $typeCarcase,
        public readonly string $generation,
        public readonly int $generationYearFrom,
        public readonly ProviderEnum $provider,
        public readonly bool $isAllow,
        public readonly ?int $generationYearTo = null,
        public readonly ?int $parentId = null,
        public readonly ?string $excelTableId = null,
        public readonly ?string $localizedName = null,
        public readonly ?string $generationShort = null,
        public readonly ?int $id = null,
        public readonly ?ManufacturerData $manufacturer = null,
        public readonly ?self $parent = null,
        public readonly ?Collection $partSpecifications = null,
    ) {}
}

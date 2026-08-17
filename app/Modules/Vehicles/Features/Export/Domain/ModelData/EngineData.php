<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class EngineData extends Data
{
    /**
     * @param  Collection<int, PartSpecificationData>|null  $partSpecifications  заполняется только
     *                                                                           Repository::forSheet(EngineExportSheetEnum::SparkPlug)
     *                                                                           (с eager load шаблона sparkPlugs)
     */
    public function __construct(
        public readonly int $engId,
        public readonly ProviderEnum $provider,
        public readonly string $codeEngine,
        public readonly int $powerKwStart,
        public readonly int $powerPsStart,
        public readonly EngineFuelTypeEnum $fuelType,
        public readonly array $allowChangeFields,
        public readonly ?int $powerKwUpto = null,
        public readonly ?int $powerPsUpto = null,
        public readonly ?float $engineCapacity = null,
        public readonly ?float $cylinderDiameter = null,
        public readonly ?int $cylinderCount = null,
        public readonly ?int $numberOfValves = null,
        public readonly ?int $groupId = null,
        public readonly ?int $id = null,
        public readonly ?Collection $partSpecifications = null,
    ) {}
}

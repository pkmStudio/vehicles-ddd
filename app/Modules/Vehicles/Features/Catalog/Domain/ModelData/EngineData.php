<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок записи двигателей для Repository и Command.
 */
#[MapName(SnakeCaseMapper::class)]
final class EngineData extends Data
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
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
    ) {}
}

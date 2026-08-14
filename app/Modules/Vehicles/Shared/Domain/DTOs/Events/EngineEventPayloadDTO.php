<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class EngineEventPayloadDTO
{
    /**
     * @param  array<int, string>  $allowChangeFields
     */
    public function __construct(
        public int $id,
        public int $engId,
        public ProviderEnum $provider,
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public EngineFuelTypeEnum $fuelType,
        public array $allowChangeFields,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?string $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $numberOfValves = null,
        public ?int $groupId = null,
    ) {}
}

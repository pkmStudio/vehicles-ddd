<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Входящий снимок двигателя, связанного с модификацией.
 */
final readonly class ModificationEngineRequestDTO
{
    /**
     * Хранит поля двигателя из сообщения мутации модификации.
     *
     * @param  array<int, string>  $allowChangeFields
     */
    public function __construct(
        public ?int $engId = null,
        public ?string $codeEngine = null,
        public ?int $powerKwStart = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsStart = null,
        public ?int $powerPsUpto = null,
        public ?string $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $numberOfValves = null,
        public ?EngineFuelTypeEnum $fuelType = null,
        public ?int $groupId = null,
        public ProviderEnum $provider = ProviderEnum::OD,
        public array $allowChangeFields = [],
    ) {}
}

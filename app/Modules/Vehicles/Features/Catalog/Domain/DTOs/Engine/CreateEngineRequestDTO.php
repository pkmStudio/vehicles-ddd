<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Передает параметры сценария или результат мутации двигателей.
 */
final readonly class CreateEngineRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public ?int $engId,
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

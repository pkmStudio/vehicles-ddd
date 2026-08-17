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
        public array $allowChangeFields,
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public EngineFuelTypeEnum $fuelType,
        public ProviderEnum $provider,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?float $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $numberOfValves = null,
        public ?int $groupId = null,
    ) {}
}

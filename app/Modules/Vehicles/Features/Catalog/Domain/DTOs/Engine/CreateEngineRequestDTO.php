<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;

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
        public int $engId,
        public ?string $codeEngine = null,
        public ?int $engPowerKwStart = null,
        public ?int $engPowerKwUpto = null,
        public ?int $engPowerPsStart = null,
        public ?int $engPowerPsUpto = null,
        public ?string $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $engNumberOfValves = null,
        public ?EngineFuelTypeEnum $engFuelType = null,
        public ?int $groupId = null,
    ) {}
}

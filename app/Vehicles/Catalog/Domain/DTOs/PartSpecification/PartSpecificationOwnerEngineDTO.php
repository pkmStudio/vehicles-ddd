<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;

/**
 * Передает данные двигателя-владельца, если его нужно создать или обновить для спеки.
 */
final readonly class PartSpecificationOwnerEngineDTO
{
    /**
     * Инициализирует immutable-снимок данных двигателя-владельца.
     */
    public function __construct(
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

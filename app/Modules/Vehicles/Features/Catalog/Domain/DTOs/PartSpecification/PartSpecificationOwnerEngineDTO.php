<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;

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
    ) {}
}

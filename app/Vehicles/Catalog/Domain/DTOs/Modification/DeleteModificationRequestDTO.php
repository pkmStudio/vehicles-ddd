<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Modification;

use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Передает параметры сценария или результат мутации модификаций.
 */
final readonly class DeleteModificationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $modId,
        public VehicleTypeEnum $type,
    ) {}
}

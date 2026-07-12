<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Modification;

use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Фиксирует доменный факт изменения модификаций.
 */
final readonly class ModificationDeleted
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(public int $userId, public string $operationId, public int $modId, public VehicleTypeEnum $type, public int $modificationId) {}
}

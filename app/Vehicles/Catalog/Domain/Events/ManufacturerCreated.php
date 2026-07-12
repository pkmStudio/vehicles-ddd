<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events;

use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;

/**
 * Фиксирует доменный факт изменения производителей.
 */
final readonly class ManufacturerCreated
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(public int $userId, public string $operationId, public ManufacturerData $manufacturer) {}
}

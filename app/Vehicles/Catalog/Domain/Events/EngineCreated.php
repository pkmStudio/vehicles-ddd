<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events;

use App\Vehicles\Catalog\Domain\ModelData\EngineData;

/**
 * Фиксирует доменный факт изменения двигателей.
 */
final readonly class EngineCreated
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(public int $userId, public string $operationId, public EngineData $engine) {}
}

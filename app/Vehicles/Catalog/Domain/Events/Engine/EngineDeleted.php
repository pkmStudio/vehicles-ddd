<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Engine;

/**
 * Фиксирует доменный факт изменения двигателей.
 */
final readonly class EngineDeleted
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(public int $userId, public string $operationId, public int $engId, public int $engineId) {}
}

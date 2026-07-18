<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Engine;

/**
 * Фиксирует доменный факт изменения двигателей.
 */
final readonly class EngineCreated
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(public int $userId, public string $operationId, public array $engine) {}
}

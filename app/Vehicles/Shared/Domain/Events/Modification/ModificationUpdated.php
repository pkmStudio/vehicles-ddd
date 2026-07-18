<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Events\Modification;

/**
 * Фиксирует доменный факт изменения модификаций.
 */
final readonly class ModificationUpdated
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(public int $userId, public string $operationId, public array $modification) {}
}

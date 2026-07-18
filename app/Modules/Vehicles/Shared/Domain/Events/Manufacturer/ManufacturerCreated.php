<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Manufacturer;

/**
 * Фиксирует доменный факт изменения производителей.
 */
final readonly class ManufacturerCreated
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(public int $userId, public string $operationId, public array $manufacturer) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Engine;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения двигателей.
 */
final readonly class EngineCreated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $engine;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $engine
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $engine)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->engine = is_array($engine)
            ? CatalogEventPayloadDTO::fromArray($engine, 'eng_id')
            : $engine;
    }
}

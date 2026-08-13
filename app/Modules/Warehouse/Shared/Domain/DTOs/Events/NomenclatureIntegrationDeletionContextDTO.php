<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Events;

final readonly class NomenclatureIntegrationDeletionContextDTO
{
    /**
     * Хранит внешний integration context удаляемой номенклатуры для shared event payload.
     */
    public function __construct(
        public int $id,
        public string $provider,
        public ?string $externalId = null,
        public ?string $externalCode = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (int) $payload['id'],
            provider: (string) $payload['provider'],
            externalId: isset($payload['external_id']) ? (string) $payload['external_id'] : null,
            externalCode: isset($payload['external_code']) ? (string) $payload['external_code'] : null,
        );
    }
}

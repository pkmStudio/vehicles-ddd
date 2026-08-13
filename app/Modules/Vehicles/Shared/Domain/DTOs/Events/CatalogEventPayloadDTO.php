<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

final readonly class CatalogEventPayloadDTO
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function __construct(
        public ?int $id,
        public ?string $businessKey,
        private array $snapshot,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, ?string $businessKeyField = null): self
    {
        $id = $payload['id'] ?? null;
        $businessKey = $businessKeyField === null ? null : ($payload[$businessKeyField] ?? null);

        return new self(
            id: is_numeric($id) ? (int) $id : null,
            businessKey: $businessKey === null ? null : (string) $businessKey,
            snapshot: $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->snapshot;
    }
}

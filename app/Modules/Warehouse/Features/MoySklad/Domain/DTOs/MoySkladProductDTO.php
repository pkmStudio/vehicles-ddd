<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\DTOs;

final readonly class MoySkladProductDTO
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $id,
        public ?string $externalCode,
        private array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $externalCode = $data['externalCode'] ?? null;

        return new self(
            id: is_string($id) && $id !== '' ? $id : null,
            externalCode: is_string($externalCode) && $externalCode !== '' ? $externalCode : null,
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->raw;

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->externalCode !== null) {
            $data['externalCode'] = $this->externalCode;
        }

        return $data;
    }
}

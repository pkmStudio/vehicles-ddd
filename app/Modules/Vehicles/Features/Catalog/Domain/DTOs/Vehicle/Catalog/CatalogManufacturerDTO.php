<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;

/**
 * Публичная REST-проекция производителя для каталога.
 */
final readonly class CatalogManufacturerDTO
{
    /**
     * Хранит публичные поля производителя для ответа каталога.
     */
    public function __construct(
        public int $id,
        public int $mfaId,
        public string $name,
    ) {}

    /**
     * Собирает публичную проекцию производителя из Data-снимка.
     */
    public static function fromData(ManufacturerData $manufacturer): self
    {
        return new self(
            id: (int) $manufacturer->id,
            mfaId: $manufacturer->mfaId,
            name: $manufacturer->name,
        );
    }

    /** @return array{id: int, mfa_id: int, name: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mfa_id' => $this->mfaId,
            'name' => $this->name,
        ];
    }
}

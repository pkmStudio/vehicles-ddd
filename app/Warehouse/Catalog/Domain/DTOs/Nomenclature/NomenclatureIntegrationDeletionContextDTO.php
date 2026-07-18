<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Nomenclature;

/**
 * Снимок integration-связи перед физическим удалением Warehouse-номенклатуры.
 */
final readonly class NomenclatureIntegrationDeletionContextDTO
{
    /**
     * Хранит generic внешний контекст для consumers события удаления.
     */
    public function __construct(
        public int $id,
        public string $provider,
        public ?string $externalId = null,
        public ?string $externalCode = null,
    ) {}

    /**
     * Возвращает payload для shared event без зависимости на DTO-класс Catalog.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'external_id' => $this->externalId,
            'external_code' => $this->externalCode,
        ];
    }
}

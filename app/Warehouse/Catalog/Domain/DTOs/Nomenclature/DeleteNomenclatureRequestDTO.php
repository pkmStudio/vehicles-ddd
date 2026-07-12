<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Nomenclature;

/**
 * DTO входящей команды на удаление Warehouse-номенклатуры.
 */
final readonly class DeleteNomenclatureRequestDTO
{
    /**
     * Хранит id удаляемой номенклатуры и контекст операции.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
    ) {}
}

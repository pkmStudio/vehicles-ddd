<?php

declare(strict_types=1);

namespace App\Warehouse\Shared\Domain\Events\Nomenclature;

/**
 * Доменный факт удаления Warehouse-номенклатуры.
 */
final readonly class NomenclatureDeleted
{
    /**
     * Хранит контекст операции и id удалённой номенклатуры.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $nomenclatureId,
        public string $partNumber,
    ) {}
}

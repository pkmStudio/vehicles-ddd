<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\Nomenclature;

use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;

/**
 * Доменный факт создания Warehouse-номенклатуры.
 */
final readonly class NomenclatureCreated
{
    /**
     * Хранит контекст операции и созданную номенклатуру.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public NomenclatureData $nomenclature,
    ) {}
}

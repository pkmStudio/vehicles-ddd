<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Events;

/**
 * Общая форма факта завершения Warehouse-импорта. Родственные события (Nomenclature/
 * PackDimension) получают одну и ту же реакцию слушателей — общий предок избавляет от N копий
 * листенеров (см. ARCHITECTURE.md §2).
 */
abstract readonly class AbstractImportCompleted
{
    /**
     * Хранит инициатора (если был), cache-ключ накопленных failures и operationId внешнего прогона.
     */
    public function __construct(
        public ?int $userId,
        public string $cacheKey,
        public ?string $operationId,
    ) {}
}

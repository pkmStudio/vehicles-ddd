<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

/**
 * Доменный факт обновления Warehouse-номенклатуры.
 */
final readonly class NomenclatureUpdated
{
    /**
     * Хранит контекст операции и обновлённую номенклатуру.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $nomenclature,
    ) {}
}

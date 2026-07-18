<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

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
        public array $nomenclature,
    ) {}
}

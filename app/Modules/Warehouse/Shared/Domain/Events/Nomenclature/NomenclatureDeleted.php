<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

/**
 * Доменный факт удаления Warehouse-номенклатуры.
 */
final readonly class NomenclatureDeleted
{
    /**
     * Хранит контекст операции, локальный id и внешний контекст удалённой номенклатуры.
     *
     * @param  array<int, array<string, mixed>>  $integrations
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $nomenclatureId,
        public string $partNumber,
        public array $integrations = [],
    ) {}
}

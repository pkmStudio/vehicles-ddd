<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\DTOs;

/**
 * DTO сортировки внешнего запроса на экспорт Warehouse-наборов.
 */
final readonly class KitExportSortDTO
{
    /**
     * Хранит поле и направление сортировки Kit Export.
     */
    public function __construct(
        public string $field = 'id',
        public string $direction = 'asc',
    ) {}
}

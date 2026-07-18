<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\DTOs;

/**
 * DTO явных фильтров внешнего запроса на экспорт Warehouse-наборов.
 */
final readonly class KitExportFiltersDTO
{
    /**
     * Хранит нормализованные фильтры Kit Export; пустые списки означают отсутствие фильтра.
     *
     * @param  list<int>  $ids
     * @param  list<int>  $typeIds
     * @param  list<string>  $nomenclaturePartNumbers
     */
    public function __construct(
        public array $ids = [],
        public array $typeIds = [],
        public ?bool $isActive = null,
        public ?bool $isSaleSeparately = null,
        public array $nomenclaturePartNumbers = [],
        public ?string $search = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Собирает CRM DTO страницы из коллекции list items и paginator.
 */
final readonly class NomenclatureCrmPageDTOFactory
{
    /**
     * Получает factory метаданных пагинации.
     *
     * Шаги:
     * 1) Принять NomenclatureCrmPaginationMetaDTOFactory из DI container.
     * 2) Использовать factory для meta блока CRM response.
     */
    public function __construct(
        private NomenclatureCrmPaginationMetaDTOFactory $metaFactory,
    ) {}

    /**
     * @param  Collection<int, NomenclatureCrmListItemDTO>  $items
     *
     * Шаги:
     * 1) Принять элементы текущей страницы и paginator.
     * 2) Собрать DTO метаданных из paginator.
     * 3) Вернуть DTO страницы с data и meta для CRM-ответа.
     */
    public function make(Collection $items, LengthAwarePaginator $paginator): NomenclatureCrmPageDTO
    {
        return new NomenclatureCrmPageDTO(
            data: $items,
            meta: $this->metaFactory->make($paginator),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPaginationMetaDTO;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Собирает CRM DTO метаданных пагинации из Laravel paginator.
 */
final readonly class NomenclatureCrmPaginationMetaDTOFactory
{
    /**
     * Собирает meta DTO для paginated CRM response.
     *
     * Шаги:
     * 1) Принять Laravel paginator результата.
     * 2) Считать текущую страницу, размер, total и last page.
     * 3) Вернуть DTO метаданных для CRM-страницы.
     */
    public function make(LengthAwarePaginator $paginator): NomenclatureCrmPaginationMetaDTO
    {
        return new NomenclatureCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}

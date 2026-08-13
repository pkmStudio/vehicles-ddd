<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Http\Request;

final readonly class NomenclatureCrmReadQueryFactory
{
    /**
     * Собирает DTO параметров CRM-чтения номенклатуры из HTTP request.
     *
     * Шаги:
     * 1) Считать page/per_page/search/sort из query string.
     * 2) Нормализовать filter в массив.
     * 3) Вернуть NomenclatureCrmReadQueryDTO с ограниченным page size.
     */
    public function make(Request $request): NomenclatureCrmReadQueryDTO
    {
        $search = trim($request->string('search')->toString());
        $filters = $request->query('filter', []);

        return new NomenclatureCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: $search === '' ? null : $search,
            sort: $request->string('sort', 'id')->toString(),
            filters: is_array($filters) ? $filters : [],
        );
    }
}

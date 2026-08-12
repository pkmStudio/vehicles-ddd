<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use Illuminate\Http\Request;

/**
 * Собирает read-query DTO Warehouse-брендов из HTTP request.
 */
final readonly class BrandCrmReadQueryFactory
{
    public function make(Request $request): BrandCrmReadQueryDTO
    {
        $filters = $request->query('filter');

        return new BrandCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: trim($request->string('search')->toString()) ?: null,
            sort: trim($request->string('sort', 'id')->toString()) ?: 'id',
            filters: is_array($filters) ? $filters : [],
        );
    }
}

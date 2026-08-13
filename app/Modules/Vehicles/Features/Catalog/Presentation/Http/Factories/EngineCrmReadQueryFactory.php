<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;
use Illuminate\Http\Request;

/**
 * Собирает read-query DTO двигателей из HTTP request.
 */
final readonly class EngineCrmReadQueryFactory
{
    public function make(Request $request): EngineCrmReadQueryDTO
    {
        $filters = $request->query('filter');

        return new EngineCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: trim($request->string('search')->toString()) ?: null,
            sort: trim($request->string('sort', 'id')->toString()) ?: 'id',
            filters: is_array($filters) ? $filters : [],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Http\Request;

final readonly class VehicleCrmReadQueryFactory
{
    public function make(Request $request): VehicleCrmReadQueryDTO
    {
        $search = trim($request->string('search')->toString());
        $filters = $request->query('filter', []);

        return new VehicleCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: $search === '' ? null : $search,
            sort: $request->string('sort', 'id')->toString(),
            filters: is_array($filters) ? $filters : [],
        );
    }
}

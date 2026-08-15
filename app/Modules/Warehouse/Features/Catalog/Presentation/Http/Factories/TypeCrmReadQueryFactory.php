<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadFiltersDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;
use Illuminate\Http\Request;

/**
 * Собирает read-query DTO Warehouse-типов из HTTP request.
 */
final readonly class TypeCrmReadQueryFactory
{
    public function make(Request $request): TypeCrmReadQueryDTO
    {
        $filters = $request->query('filter');
        $filterData = is_array($filters) ? $filters : [];

        return new TypeCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: trim($request->string('search')->toString()) ?: null,
            sort: trim($request->string('sort', 'id')->toString()) ?: 'id',
            filters: new TypeCrmReadFiltersDTO(
                name: $this->nullableString($filterData, 'name'),
                char: $this->nullableString($filterData, 'char'),
            ),
        );
    }

    /**
     * @param  array<array-key, int|float|string|bool|null|array<array-key, int|float|string|bool|null>>  $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (is_array($value)) {
            $value = $value['value'] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value) ?: null;
    }
}

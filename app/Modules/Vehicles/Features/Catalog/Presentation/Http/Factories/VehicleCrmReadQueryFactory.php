<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Http\Request;

/**
 * Собирает DTO запроса для CRM-списка ТС из параметров HTTP-запроса.
 */
final readonly class VehicleCrmReadQueryFactory
{
    /**
     * Нормализует параметры списка ТС для репозитория CRM-чтения.
     *
     * Шаги:
     * - Обрезать поисковую строку и заменить пустую строку на null.
     * - Ограничить номер страницы и per_page допустимыми границами.
     * - Передать сортировку и массив фильтров в DTO запроса чтения.
     */
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

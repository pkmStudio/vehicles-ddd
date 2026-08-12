<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Http\Request;

/**
 * Собирает read-query DTO комплектов из HTTP request.
 */
final readonly class KitCrmReadQueryFactory
{
    /**
     * Создает read-query DTO для CRM списка комплектов.
     *
     * Шаги:
     * 1. Читает пагинацию, search, сортировку и filter параметры HTTP request.
     * 2. Нормализует page/per_page в допустимые границы.
     * 3. Возвращает DTO без передачи HTTP request глубже presentation границу.
     */
    public function make(Request $request): KitCrmReadQueryDTO
    {
        $filters = $request->query('filter');

        return new KitCrmReadQueryDTO(
            page: max(1, (int) $request->integer('page', 1)),
            perPage: min(max((int) $request->integer('per_page', 25), 1), 100),
            search: trim($request->string('search')->toString()) ?: null,
            sort: trim($request->string('sort', 'id')->toString()) ?: 'id',
            filters: is_array($filters) ? $filters : [],
        );
    }
}

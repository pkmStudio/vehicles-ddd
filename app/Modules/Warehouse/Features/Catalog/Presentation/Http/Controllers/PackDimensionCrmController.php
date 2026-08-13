<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\PackDimensionCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories\PackDimensionCrmReadQueryFactory;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\PackDimensionCrmReadPresenter;
use App\Support\Http\Presenters\HttpArrayPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-упаковочных размеров.
 */
final readonly class PackDimensionCrmController
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Инициализирует зависимости HTTP adapter-а упаковочных размеров.
     *
     * Шаги:
     * 1. Получает CRM клиент, query фабрику и presenters из контейнера.
     * 2. Сохраняет зависимости для обработки HTTP read endpoints.
     */
    public function __construct(
        private PackDimensionCrmClientInterface $packDimensions,
        private PackDimensionCrmReadQueryFactory $queryFactory,
        private PackDimensionCrmReadPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает постраничный список упаковочных размеров для CRM.
     *
     * Шаги:
     * 1. Собирает read-query DTO из HTTP request.
     * 2. Запрашивает страницу данных через CRM клиент.
     * 3. Преобразует DTO страницы в форму HTTP-ответа.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->packDimensions->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок упаковочного размера для CRM.
     *
     * Шаги:
     * 1. Запрашивает detail DTO по id через CRM клиент.
     * 2. Возвращает `404`, если упаковочный размер не найден.
     * 3. Преобразует найденный DTO в форму HTTP-ответа.
     */
    public function show(int $id): JsonResponse
    {
        $packDimension = $this->packDimensions->show($id);

        if ($packDimension === null) {
            return response()->json(['message' => 'Pack dimension not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($packDimension)]);
    }

    /**
     * Возвращает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Нормализует query, id и лимит из HTTP request.
     * 2. Запрашивает options через CRM клиент.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function types(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $limit = min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);

        $types = $this->packDimensions->types(
            query: $query === '' ? null : $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($types)]);
    }
}

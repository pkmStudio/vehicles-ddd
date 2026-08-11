<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories\KitCrmReadQueryFactory;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\KitCrmReadPresenter;
use App\Support\Http\Presenters\HttpArrayPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-комплектов.
 */
final readonly class KitCrmController
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Инициализирует зависимости HTTP adapter-а комплектов.
     *
     * Шаги:
     * 1. Получает CRM client, query factory и presenters из контейнера.
     * 2. Сохраняет зависимости для обработки HTTP read endpoints.
     */
    public function __construct(
        private KitCrmClientInterface $kits,
        private KitCrmReadQueryFactory $queryFactory,
        private KitCrmReadPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает постраничный список комплектов для CRM.
     *
     * Шаги:
     * 1. Собирает read-query DTO из HTTP request.
     * 2. Запрашивает страницу данных через CRM client.
     * 3. Преобразует page DTO в HTTP response shape.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->kits->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок комплекта для CRM.
     *
     * Шаги:
     * 1. Запрашивает detail DTO по id через CRM client.
     * 2. Возвращает `404`, если комплект не найден.
     * 3. Преобразует найденный DTO в HTTP response shape.
     */
    public function show(int $id): JsonResponse
    {
        $kit = $this->kits->show($id);

        if ($kit === null) {
            return response()->json(['message' => 'Kit not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($kit)]);
    }

    /**
     * Возвращает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Нормализует query, id и limit из HTTP request.
     * 2. Запрашивает options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function nomenclatures(Request $request): JsonResponse
    {
        $options = $this->kits->nomenclatures(
            query: $this->query($request),
            id: $this->id($request),
            limit: $this->limit($request),
        );

        return response()->json(['data' => $this->arrays->collection($options)]);
    }

    /**
     * Возвращает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Нормализует query, id и limit из HTTP request.
     * 2. Запрашивает options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function packDimensions(Request $request): JsonResponse
    {
        $options = $this->kits->packDimensions(
            query: $this->query($request),
            id: $this->id($request),
            limit: $this->limit($request),
        );

        return response()->json(['data' => $this->arrays->collection($options)]);
    }

    /**
     * Возвращает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Нормализует query, id и limit из HTTP request.
     * 2. Запрашивает options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function types(Request $request): JsonResponse
    {
        $options = $this->kits->types(
            query: $this->query($request),
            id: $this->id($request),
            limit: $this->limit($request),
        );

        return response()->json(['data' => $this->arrays->collection($options)]);
    }

    /**
     * Нормализует limit options endpoint-а.
     *
     * Шаги:
     * 1. Читает `limit` из HTTP request с дефолтом 50.
     * 2. Ограничивает значение диапазоном `1..OPTION_LIMIT`.
     */
    private function limit(Request $request): int
    {
        return min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);
    }

    /**
     * Нормализует selected id options endpoint-а.
     *
     * Шаги:
     * 1. Проверяет наличие query-параметра `id`.
     * 2. Возвращает `null` или integer id.
     */
    private function id(Request $request): ?int
    {
        return $request->query('id') === null ? null : (int) $request->integer('id');
    }

    /**
     * Нормализует поисковую строку options endpoint-а.
     *
     * Шаги:
     * 1. Читает query-параметр `q`.
     * 2. Убирает внешние пробелы.
     * 3. Возвращает `null`, если строка пустая.
     */
    private function query(Request $request): ?string
    {
        $query = trim($request->string('q')->toString());

        return $query === '' ? null : $query;
    }
}

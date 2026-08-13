<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\NomenclatureCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories\NomenclatureCrmReadQueryFactory;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\NomenclatureCrmReadPresenter;
use App\Support\Http\Presenters\HttpArrayPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-номенклатуры.
 */
final readonly class NomenclatureCrmController
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Получает CRM read клиент, query фабрику и presenters форму HTTP-ответа.
     * Шаги:
     * 1) Сохранить read клиент Warehouse-номенклатуры.
     * 2) Сохранить фабрику, которая переводит HTTP query в read DTO.
     * 3) Сохранить presenter detail/page shape.
     * 4) Сохранить generic array presenter для option collections.
     */
    public function __construct(
        private NomenclatureCrmClientInterface $nomenclatures,
        private NomenclatureCrmReadQueryFactory $queryFactory,
        private NomenclatureCrmReadPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает постраничный список номенклатуры для CRM.
     * Шаги:
     * 1) Собрать NomenclatureCrmReadQueryDTO из HTTP request.
     * 2) Получить страницу номенклатуры через read клиент.
     * 3) Преобразовать DTO страницы в wire array через presenter.
     * 4) Вернуть JSON ответ.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->nomenclatures->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок номенклатуры для CRM.
     * Шаги:
     * 1) Запросить номенклатуру по route id через read клиент.
     * 2) Для отсутствующей записи вернуть 404 JSON.
     * 3) Для найденной записи отрендерить detail DTO в wire array.
     * 4) Вернуть JSON ответ с data.
     */
    public function show(int $id): JsonResponse
    {
        $nomenclature = $this->nomenclatures->show($id);

        if ($nomenclature === null) {
            return response()->json(['message' => 'Nomenclature not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($nomenclature)]);
    }

    /**
     * Возвращает compact options для поиска номенклатуры.
     * Шаги:
     * 1) Нормализовать лимит в диапазон 1..50.
     * 2) Прочитать q строку поиска из request.
     * 3) Получить search items через read клиент.
     * 4) Преобразовать collection DTO в plain arrays и вернуть JSON.
     */
    public function search(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 20), 1), 50);
        $query = trim($request->string('q')->toString());

        $items = $this->nomenclatures->search(
            query: $query,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($items)]);
    }

    /**
     * Возвращает type options для CRM-формы номенклатуры.
     * Шаги:
     * 1) Нормализовать лимит в диапазон 1..OPTION_LIMIT.
     * 2) Прочитать необязательный id поиск и q строку поиска.
     * 3) Передать null вместо пустого search.
     * 4) Получить type options через read клиент и вернуть JSON data.
     */
    public function types(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $types = $this->nomenclatures->types(
            query: $query === '' ? null : $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($types)]);
    }

    /**
     * Возвращает brand options для CRM-формы номенклатуры.
     * Шаги:
     * 1) Нормализовать лимит в диапазон 1..OPTION_LIMIT.
     * 2) Прочитать необязательный id поиск и q строку поиска.
     * 3) Передать null вместо пустого search.
     * 4) Получить brand options через read клиент и вернуть JSON data.
     */
    public function brands(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $brands = $this->nomenclatures->brands(
            query: $query === '' ? null : $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($brands)]);
    }
}

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

    public function __construct(
        private NomenclatureCrmClientInterface $nomenclatures,
        private NomenclatureCrmReadQueryFactory $queryFactory,
        private NomenclatureCrmReadPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает постраничный список номенклатуры для CRM.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->nomenclatures->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок номенклатуры для CRM.
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

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\NomenclatureCatalogClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\NomenclatureCatalogPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter read API Warehouse-номенклатуры для dan-catalog.
 */
final readonly class NomenclatureCatalogController
{
    /**
     * Получает catalog client и HTTP presenter.
     */
    public function __construct(
        private NomenclatureCatalogClientInterface $catalog,
        private NomenclatureCatalogPresenter $presenter,
    ) {}

    /**
     * Возвращает непустые категории номенклатуры выбранного бренда.
     */
    public function categories(Request $request): JsonResponse
    {
        $brandId = $this->brandId($request);

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $categories = $this->catalog->categories($brandId);
        $payload = ['data' => $this->presenter->categories($categories)];

        return response()->json($payload);
    }

    /**
     * Возвращает страницу номенклатур выбранной категории.
     */
    public function nomenclatures(Request $request, int $category): JsonResponse
    {
        $page = $request->integer('page', 1);
        $pageSize = $request->integer('page_size', 9);
        $brandId = $this->brandId($request);

        if ($page < 1 || $pageSize < 1 || $pageSize > 100) {
            return response()->json(['message' => 'Invalid pagination parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $result = $this->catalog->nomenclatures(
            categoryId: $category,
            brandId: $brandId,
            page: $page,
            pageSize: $pageSize,
        );

        if ($result === null) {
            return response()->json(['message' => 'Category not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->presenter->page($result);

        return response()->json(['data' => $payload]);
    }

    /**
     * Возвращает детальную номенклатуру по артикулу.
     */
    public function show(Request $request, string $partNumber): JsonResponse
    {
        $partNumber = trim($partNumber);
        $brandId = $this->brandId($request);

        if ($partNumber === '') {
            return response()->json(['message' => 'Part number is required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $nomenclature = $this->catalog->nomenclature(
            partNumber: $partNumber,
            brandId: $brandId,
        );

        if ($nomenclature === null) {
            return response()->json(['message' => 'Nomenclature not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->presenter->nomenclature($nomenclature);

        return response()->json(['data' => $payload]);
    }

    /**
     * Возвращает ограниченный результат поиска по артикулу и имени.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = $request->integer('limit', 8);
        $brandId = $this->brandId($request);

        if ($query === '' || $limit < 1 || $limit > 20) {
            return response()->json(['message' => 'Invalid search parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $result = $this->catalog->search(
            query: $query,
            brandId: $brandId,
            limit: $limit,
        );
        $payload = $this->presenter->search($result);

        return response()->json(['data' => $payload]);
    }

    /**
     * Читает положительный brand_id или использует настроенный бренд каталога.
     */
    private function brandId(Request $request): ?int
    {
        if (! $request->query->has('brand_id')) {
            return (int) config('services.dan_catalog.brand_id', 3);
        }

        $brandId = filter_var(
            $request->query('brand_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return is_int($brandId) ? $brandId : null;
    }

    /**
     * Возвращает единый ответ для невалидного brand_id.
     */
    private function invalidBrandResponse(): JsonResponse
    {
        return response()->json(['message' => 'Invalid brand parameter.'], Response::HTTP_BAD_REQUEST);
    }
}

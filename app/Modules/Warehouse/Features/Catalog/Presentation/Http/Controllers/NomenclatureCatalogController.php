<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ListCatalogCategoriesUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ListCategoryNomenclaturesUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\SearchCatalogNomenclaturesUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ShowCatalogNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter read API Warehouse-номенклатуры для dan-catalog.
 */
final readonly class NomenclatureCatalogController
{
    private const int DAN_BRAND_ID = 3;

    public function __construct(
        private ListCatalogCategoriesUseCaseInterface $listCategories,
        private ListCategoryNomenclaturesUseCaseInterface $listNomenclatures,
        private ShowCatalogNomenclatureUseCaseInterface $showNomenclature,
        private SearchCatalogNomenclaturesUseCaseInterface $searchNomenclatures,
    ) {}

    public function categories(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $brandId = $this->brandId($request);

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        return response()->json([
            'data' => $this->listCategories->execute($brandId)
                ->map(static fn (CatalogCategoryDTO $category): array => $category->toArray())
                ->values()
                ->all(),
        ]);
    }

    public function nomenclatures(Request $request, int $category): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $page = $request->integer('page', 1);
        $pageSize = $request->integer('page_size', 9);
        $brandId = $this->brandId($request);

        if ($page < 1 || $pageSize < 1 || $pageSize > 100) {
            return response()->json(['message' => 'Invalid pagination parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $result = $this->listNomenclatures->execute($category, $brandId, $page, $pageSize);

        if ($result === null) {
            return response()->json(['message' => 'Category not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(Request $request, string $partNumber): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $partNumber = trim($partNumber);
        $brandId = $this->brandId($request);

        if ($partNumber === '') {
            return response()->json(['message' => 'Part number is required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        $nomenclature = $this->showNomenclature->execute($partNumber, $brandId);

        if ($nomenclature === null) {
            return response()->json(['message' => 'Nomenclature not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $nomenclature->toArray()]);
    }

    public function search(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $query = trim((string) $request->query('q', ''));
        $limit = $request->integer('limit', 8);
        $brandId = $this->brandId($request);

        if ($query === '' || $limit < 1 || $limit > 20) {
            return response()->json(['message' => 'Invalid search parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return $this->invalidBrandResponse();
        }

        return response()->json([
            'data' => $this->searchNomenclatures->execute($query, $brandId, $limit)->toArray(),
        ]);
    }

    private function brandId(Request $request): ?int
    {
        if (! $request->query->has('brand_id')) {
            return self::DAN_BRAND_ID;
        }

        $brandId = filter_var(
            $request->query('brand_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return is_int($brandId) ? $brandId : null;
    }

    private function invalidBrandResponse(): JsonResponse
    {
        return response()->json(['message' => 'Invalid brand parameter.'], Response::HTTP_BAD_REQUEST);
    }

    private function guard(Request $request): ?JsonResponse
    {
        $key = (string) config('services.dan_catalog.read_api_key', '');

        if ($key === '') {
            return null;
        }

        if (hash_equals($key, (string) $request->header('X-Service-Key'))) {
            return null;
        }

        return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
    }
}

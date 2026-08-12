<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\BrandCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories\BrandCrmReadQueryFactory;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\BrandCrmReadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-брендов.
 */
final readonly class BrandCrmController
{
    public function __construct(
        private BrandCrmClientInterface $brands,
        private BrandCrmReadQueryFactory $queryFactory,
        private BrandCrmReadPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->brands->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    public function show(int $id): JsonResponse
    {
        $brand = $this->brands->show($id);

        if ($brand === null) {
            return response()->json(['message' => 'Brand not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($brand)]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\TypeCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Factories\TypeCrmReadQueryFactory;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters\TypeCrmReadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-типов.
 */
final readonly class TypeCrmController
{
    public function __construct(
        private TypeCrmClientInterface $types,
        private TypeCrmReadQueryFactory $queryFactory,
        private TypeCrmReadPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->types->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    public function show(int $id): JsonResponse
    {
        $type = $this->types->show($id);

        if ($type === null) {
            return response()->json(['message' => 'Type not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($type)]);
    }
}

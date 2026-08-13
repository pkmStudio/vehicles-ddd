<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\ManufacturerCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories\ManufacturerCrmReadQueryFactory;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\ManufacturerCrmReadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для производителей.
 */
final readonly class ManufacturerCrmController
{
    public function __construct(
        private ManufacturerCrmClientInterface $manufacturers,
        private ManufacturerCrmReadQueryFactory $queryFactory,
        private ManufacturerCrmReadPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->manufacturers->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    public function show(int $id): JsonResponse
    {
        $manufacturer = $this->manufacturers->show($id);

        if ($manufacturer === null) {
            return response()->json(['message' => 'Manufacturer not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($manufacturer)]);
    }
}

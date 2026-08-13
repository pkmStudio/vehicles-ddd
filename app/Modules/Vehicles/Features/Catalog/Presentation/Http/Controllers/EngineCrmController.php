<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\EngineCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories\EngineCrmReadQueryFactory;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories\VehicleCrmReadQueryFactory;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\EngineCrmReadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для двигателей.
 */
final readonly class EngineCrmController
{
    public function __construct(
        private EngineCrmClientInterface $engines,
        private VehicleCrmClientInterface $vehicles,
        private EngineCrmReadQueryFactory $queryFactory,
        private VehicleCrmReadQueryFactory $vehicleRelationQueryFactory,
        private EngineCrmReadPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->engines->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    public function show(int $id): JsonResponse
    {
        $engine = $this->engines->show($id);

        if ($engine === null) {
            return response()->json(['message' => 'Engine not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($engine)]);
    }

    public function forVehicle(int $id, Request $request): JsonResponse
    {
        $query = $this->vehicleRelationQueryFactory->make($request);
        $page = $this->vehicles->engines($id, $query);

        return response()->json($this->presenter->relationPage($page));
    }
}

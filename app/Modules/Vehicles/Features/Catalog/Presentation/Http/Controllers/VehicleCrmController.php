<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories\VehicleCrmReadQueryFactory;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\VehicleCrmReadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для каталога Vehicles.
 */
final readonly class VehicleCrmController
{
    /**
     * Получает use case ports CRM read API.
     */
    public function __construct(
        private ListVehiclesForCrmUseCaseInterface $listVehicles,
        private ShowVehicleForCrmUseCaseInterface $showVehicle,
        private SearchVehiclesForCrmUseCaseInterface $searchVehicles,
        private ListVehicleCrmOptionsUseCaseInterface $vehicleOptions,
        private VehicleCrmReadQueryFactory $queryFactory,
        private VehicleCrmReadPresenter $presenter,
    ) {}

    /**
     * Возвращает постраничный список ТС для CRM.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $query = $this->queryFactory->make($request);
        $page = $this->listVehicles->execute($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок ТС для CRM.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $vehicle = $this->showVehicle->execute($id);

        if ($vehicle === null) {
            return response()->json(['message' => 'Vehicle not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($vehicle)]);
    }

    /**
     * Возвращает compact options для поиска ТС.
     */
    public function search(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 20), 1), 50);
        $query = trim($request->string('q')->toString());

        $items = $this->searchVehicles->execute(
            query: $query,
            limit: $limit,
        );

        return response()->json(['data' => $this->presenter->collection($items)]);
    }

    /**
     * Возвращает feature options для CRM-формы.
     */
    public function features(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $features = $this->vehicleOptions->features();

        return response()->json(['data' => $this->presenter->collection($features)]);
    }

    /**
     * Возвращает feature value options для CRM-формы.
     */
    public function featureValues(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $featureId = (int) $request->integer('feature_id');
        $featureValues = $this->vehicleOptions->featureValues($featureId);

        return response()->json(['data' => $this->presenter->collection($featureValues)]);
    }

    /**
     * Возвращает detail template options для CRM-формы.
     */
    public function detailTemplates(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $detailTemplates = $this->vehicleOptions->detailTemplates();

        return response()->json(['data' => $this->presenter->collection($detailTemplates)]);
    }

    /**
     * Возвращает manufacturer options для CRM-формы.
     */
    public function manufacturers(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), 50);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $query = $query === '' ? null : $query;
        $manufacturers = $this->vehicleOptions->manufacturers(
            query: $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->presenter->collection($manufacturers)]);
    }

    /**
     * Проверяет service key, если он настроен.
     */
    private function guard(Request $request): ?JsonResponse
    {
        $key = (string) config('services.dan_vehicles.read_api_key', '');

        if ($key === '') {
            return null;
        }

        if (hash_equals($key, (string) $request->header('X-Service-Key'))) {
            return null;
        }

        return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
    }
}

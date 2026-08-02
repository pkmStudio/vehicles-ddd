<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
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
    ) {}

    /**
     * Возвращает постраничный список ТС для CRM.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        return response()->json(
            $this->listVehicles->execute(VehicleCrmReadQueryDTO::fromArray($request->query())),
        );
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

        return response()->json(['data' => $vehicle]);
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

        return response()->json(['data' => $this->searchVehicles->execute($query, $limit)]);
    }

    /**
     * Возвращает feature options для CRM-формы.
     */
    public function features(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        return response()->json(['data' => $this->listVehicles->features()]);
    }

    /**
     * Возвращает feature value options для CRM-формы.
     */
    public function featureValues(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        return response()->json([
            'data' => $this->listVehicles->featureValues((int) $request->integer('feature_id')),
        ]);
    }

    /**
     * Возвращает detail template options для CRM-формы.
     */
    public function detailTemplates(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        return response()->json(['data' => $this->listVehicles->detailTemplates()]);
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

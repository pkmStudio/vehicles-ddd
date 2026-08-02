<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $query = VehicleCrmReadQueryDTO::fromArray($request->query());
        $page = $this->listVehicles->execute($query);

        return response()->json(
            $page->toArray(),
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

        return response()->json(['data' => $vehicle->toArray()]);
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

        return response()->json(['data' => $this->dtoCollectionToArray($items)]);
    }

    /**
     * Возвращает feature options для CRM-формы.
     */
    public function features(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $features = $this->listVehicles->features();

        return response()->json(['data' => $this->dtoCollectionToArray($features)]);
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
        $featureValues = $this->listVehicles->featureValues($featureId);

        return response()->json(['data' => $this->dtoCollectionToArray($featureValues)]);
    }

    /**
     * Возвращает detail template options для CRM-формы.
     */
    public function detailTemplates(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $detailTemplates = $this->listVehicles->detailTemplates();

        return response()->json(['data' => $this->dtoCollectionToArray($detailTemplates)]);
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
        $manufacturers = $this->listVehicles->manufacturers(
            query: $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->dtoCollectionToArray($manufacturers)]);
    }

    /**
     * Преобразует коллекцию CRM DTO в публичный JSON payload.
     *
     * @param  Collection<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function dtoCollectionToArray(Collection $items): array
    {
        return $items
            ->map(fn (mixed $item): array => $item->toArray())
            ->values()
            ->all();
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

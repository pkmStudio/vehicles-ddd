<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\VehicleCatalogPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter read API каталога ТС для dan-catalog.
 */
final readonly class VehicleCatalogController
{
    /**
     * Получает use case ports read API каталога.
     */
    public function __construct(
        private ListManufacturersForCatalogUseCaseInterface $listManufacturers,
        private ListManufacturerVehiclesForCatalogUseCaseInterface $listVehicles,
        private ListVehicleModificationsForCatalogUseCaseInterface $listModifications,
        private ShowModificationForCatalogUseCaseInterface $showModification,
        private VehicleCatalogPresenter $presenter,
    ) {}

    /**
     * Возвращает производителей с разрешёнными ТС.
     */
    public function manufacturers(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        return response()->json([
            'data' => $this->presenter->collection($this->listManufacturers->execute()),
        ]);
    }

    /**
     * Возвращает разрешённые ТС производителя.
     */
    public function vehicles(Request $request, int $manufacturer): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $vehicles = $this->listVehicles->execute($manufacturer);

        if ($vehicles === null) {
            return response()->json(['message' => 'Manufacturer not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->collection($vehicles)]);
    }

    /**
     * Возвращает модификации разрешённого ТС.
     */
    public function modifications(Request $request, int $vehicle): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $modifications = $this->listModifications->execute($vehicle);

        if ($modifications === null) {
            return response()->json(['message' => 'Vehicle not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->collection($modifications)]);
    }

    /**
     * Возвращает модификацию с её ТС и производителем.
     */
    public function showModification(Request $request, int $modification): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $context = $this->showModification->execute($modification);

        if ($context === null) {
            return response()->json(['message' => 'Modification not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->modificationContext($context)]);
    }

    /**
     * Проверяет service key, если он настроен.
     */
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

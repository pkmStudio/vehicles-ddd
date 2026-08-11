<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCatalogClientInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\VehicleCatalogPresenter;
use App\Support\Http\Presenters\HttpArrayPresenter;
use Illuminate\Http\JsonResponse;
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
        private VehicleCatalogClientInterface $catalog,
        private VehicleCatalogPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает производителей с разрешёнными ТС.
     */
    public function manufacturers(): JsonResponse
    {
        return response()->json([
            'data' => $this->arrays->collection($this->catalog->manufacturers()),
        ]);
    }

    /**
     * Возвращает разрешённые ТС производителя.
     */
    public function vehicles(int $manufacturer): JsonResponse
    {
        $vehicles = $this->catalog->vehicles($manufacturer);

        if ($vehicles === null) {
            return response()->json(['message' => 'Manufacturer not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->arrays->collection($vehicles)]);
    }

    /**
     * Возвращает модификации разрешённого ТС.
     */
    public function modifications(int $vehicle): JsonResponse
    {
        $modifications = $this->catalog->modifications($vehicle);

        if ($modifications === null) {
            return response()->json(['message' => 'Vehicle not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->arrays->collection($modifications)]);
    }

    /**
     * Возвращает модификацию с её ТС и производителем.
     */
    public function showModification(int $modification): JsonResponse
    {
        $context = $this->catalog->modification($modification);

        if ($context === null) {
            return response()->json(['message' => 'Modification not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->modificationContext($context)]);
    }
}

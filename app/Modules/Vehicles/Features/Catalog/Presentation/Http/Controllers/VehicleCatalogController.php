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
     *
     * Шаги:
     * 1. Получает catalog client и presenters из контейнера.
     * 2. Сохраняет зависимости для обработки HTTP read endpoints.
     */
    public function __construct(
        private VehicleCatalogClientInterface $catalog,
        private VehicleCatalogPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает производителей с разрешёнными ТС.
     *
     * Шаги:
     * 1. Запрашивает список производителей через catalog client.
     * 2. Возвращает collection в стандартном `data` wrapper.
     */
    public function manufacturers(): JsonResponse
    {
        return response()->json([
            'data' => $this->arrays->collection($this->catalog->manufacturers()),
        ]);
    }

    /**
     * Возвращает разрешённые ТС производителя.
     *
     * Шаги:
     * 1. Запрашивает автомобили производителя через catalog client.
     * 2. Возвращает `404`, если производитель не найден.
     * 3. Возвращает collection в стандартном `data` wrapper.
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
     *
     * Шаги:
     * 1. Запрашивает модификации автомобиля через catalog client.
     * 2. Возвращает `404`, если автомобиль не найден.
     * 3. Возвращает collection в стандартном `data` wrapper.
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
     *
     * Шаги:
     * 1. Запрашивает detail context модификации через catalog client.
     * 2. Возвращает `404`, если модификация не найдена.
     * 3. Преобразует context DTO в HTTP response shape.
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

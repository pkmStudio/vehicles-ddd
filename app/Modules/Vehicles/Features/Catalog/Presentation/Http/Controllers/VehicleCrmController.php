<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Factories\VehicleCrmReadQueryFactory;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters\VehicleCrmReadPresenter;
use App\Support\Http\Presenters\HttpArrayPresenter;
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
     *
     * Шаги:
     * 1. Получает CRM client, query factory и presenters из контейнера.
     * 2. Сохраняет зависимости для обработки HTTP read endpoints.
     */
    public function __construct(
        private VehicleCrmClientInterface $vehicles,
        private VehicleCrmReadQueryFactory $queryFactory,
        private VehicleCrmReadPresenter $presenter,
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает постраничный список ТС для CRM.
     *
     * Шаги:
     * 1. Собирает `VehicleCrmReadQueryDTO` из HTTP request.
     * 2. Запрашивает страницу данных через CRM client.
     * 3. Преобразует page DTO в HTTP response shape.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryFactory->make($request);
        $page = $this->vehicles->paginate($query);

        return response()->json($this->presenter->page($page));
    }

    /**
     * Возвращает detail-снимок ТС для CRM.
     *
     * Шаги:
     * 1. Запрашивает detail DTO по id через CRM client.
     * 2. Возвращает `404`, если автомобиль не найден.
     * 3. Преобразует найденный DTO в HTTP response shape.
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->vehicles->show($id);

        if ($vehicle === null) {
            return response()->json(['message' => 'Vehicle not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->presenter->detail($vehicle)]);
    }

    /**
     * Возвращает compact options для поиска ТС.
     *
     * Шаги:
     * 1. Нормализует search query и limit из HTTP request.
     * 2. Запрашивает compact options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function search(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 20), 1), 50);
        $query = trim($request->string('q')->toString());

        $items = $this->vehicles->search(
            query: $query,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($items)]);
    }

    /**
     * Возвращает feature options для CRM-формы.
     *
     * Шаги:
     * 1. Запрашивает feature options через CRM client.
     * 2. Возвращает collection в стандартном `data` wrapper.
     */
    public function features(): JsonResponse
    {
        $features = $this->vehicles->features();

        return response()->json(['data' => $this->arrays->collection($features)]);
    }

    /**
     * Возвращает feature value options для CRM-формы.
     *
     * Шаги:
     * 1. Извлекает `feature_id` из HTTP request.
     * 2. Запрашивает feature value options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function featureValues(Request $request): JsonResponse
    {
        $featureId = (int) $request->integer('feature_id');
        $featureValues = $this->vehicles->featureValues($featureId);

        return response()->json(['data' => $this->arrays->collection($featureValues)]);
    }

    /**
     * Возвращает detail template options для CRM-формы.
     *
     * Шаги:
     * 1. Запрашивает detail template options через CRM client.
     * 2. Возвращает collection в стандартном `data` wrapper.
     */
    public function detailTemplates(): JsonResponse
    {
        $detailTemplates = $this->vehicles->detailTemplates();

        return response()->json(['data' => $this->arrays->collection($detailTemplates)]);
    }

    /**
     * Возвращает manufacturer options для CRM-формы.
     *
     * Шаги:
     * 1. Нормализует selected id, search query и limit из HTTP request.
     * 2. Запрашивает manufacturer options через CRM client.
     * 3. Возвращает collection в стандартном `data` wrapper.
     */
    public function manufacturers(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 1), 50);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $query = $query === '' ? null : $query;
        $manufacturers = $this->vehicles->manufacturers(
            query: $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->arrays->collection($manufacturers)]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ListNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\SearchNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ShowNomenclatureForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter CRM read API для Warehouse-номенклатуры.
 */
final readonly class NomenclatureCrmController
{
    private const int OPTION_LIMIT = 1000;

    public function __construct(
        private ListNomenclaturesForCrmUseCaseInterface $listNomenclatures,
        private ShowNomenclatureForCrmUseCaseInterface $showNomenclature,
        private SearchNomenclaturesForCrmUseCaseInterface $searchNomenclatures,
    ) {}

    /**
     * Возвращает постраничный список номенклатуры для CRM.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $query = NomenclatureCrmReadQueryDTO::fromArray($request->query());
        $page = $this->listNomenclatures->execute($query);

        return response()->json($page->toArray());
    }

    /**
     * Возвращает detail-снимок номенклатуры для CRM.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $nomenclature = $this->showNomenclature->execute($id);

        if ($nomenclature === null) {
            return response()->json(['message' => 'Nomenclature not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $nomenclature->toArray()]);
    }

    /**
     * Возвращает compact options для поиска номенклатуры.
     */
    public function search(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 20), 1), 50);
        $query = trim($request->string('q')->toString());

        $items = $this->searchNomenclatures->execute(
            query: $query,
            limit: $limit,
        );

        return response()->json(['data' => $this->dtoCollectionToArray($items)]);
    }

    /**
     * Возвращает type options для CRM-формы номенклатуры.
     */
    public function types(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $types = $this->listNomenclatures->types(
            query: $query === '' ? null : $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->dtoCollectionToArray($types)]);
    }

    /**
     * Возвращает brand options для CRM-формы номенклатуры.
     */
    public function brands(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), self::OPTION_LIMIT);
        $id = $request->query('id') === null ? null : (int) $request->integer('id');
        $query = trim($request->string('q')->toString());

        $brands = $this->listNomenclatures->brands(
            query: $query === '' ? null : $query,
            id: $id,
            limit: $limit,
        );

        return response()->json(['data' => $this->dtoCollectionToArray($brands)]);
    }

    /**
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

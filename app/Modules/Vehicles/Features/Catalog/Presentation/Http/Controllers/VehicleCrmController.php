<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final readonly class VehicleCrmController
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);
        $query = $this->baseQuery();
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request->string('search')->toString());
        $this->applySort($query, $request);

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $vehicle = $this->baseQuery()
            ->where('vehicles.id', $id)
            ->first();

        if ($vehicle === null) {
            return response()->json(['message' => 'Vehicle not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $vehicle]);
    }

    public function search(Request $request): JsonResponse
    {
        if ($response = $this->guard($request)) {
            return $response;
        }

        $limit = min(max((int) $request->integer('limit', 20), 1), 50);
        $query = trim($request->string('q')->toString());

        $vehicles = $this->baseQuery()
            ->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query): void {
                $builder
                    ->where('vehicles.name', 'ilike', "%{$query}%")
                    ->orWhere('manufacturers.name', 'ilike', "%{$query}%");

                if (is_numeric($query)) {
                    $builder->orWhere('vehicles.ms_id', (int) $query);
                }
            }))
            ->orderBy('manufacturers.name')
            ->orderBy('vehicles.name')
            ->limit($limit)
            ->get()
            ->map(fn (object $vehicle): array => [
                'id' => $vehicle->id,
                'label' => trim("{$vehicle->manufacturer_name} {$vehicle->name}"),
                'ms_id' => $vehicle->ms_id,
                'manufacturer' => $vehicle->manufacturer_name,
            ]);

        return response()->json(['data' => $vehicles]);
    }

    private function baseQuery(): Builder
    {
        return DB::table('vehicles')
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'vehicles.manufacturer_id')
            ->select([
                'vehicles.id',
                'vehicles.parent_id',
                'vehicles.manufacturer_id',
                'manufacturers.name as manufacturer_name',
                'vehicles.mfa_id',
                'vehicles.ms_id',
                'vehicles.name',
                'vehicles.localized_name',
                'vehicles.generation',
                'vehicles.generation_short',
                'vehicles.generation_year_from',
                'vehicles.generation_year_to',
                'vehicles.type',
                'vehicles.type_carcase',
                'vehicles.provider',
                'vehicles.steering_type',
                'vehicles.is_allow',
            ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $filters = $request->array('filter');

        foreach (['manufacturer_id', 'name', 'type_carcase', 'provider'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $column = $field === 'manufacturer_id' ? 'vehicles.manufacturer_id' : "vehicles.{$field}";
            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn($column, $values);
        }

        if (isset($filters['manufacturer_name']) && $filters['manufacturer_name'] !== '') {
            $query->where('manufacturers.name', 'ilike', '%'.(string) $filters['manufacturer_name'].'%');
        }

        if (array_key_exists('is_allow', $filters) && $filters['is_allow'] !== '') {
            $query->where('vehicles.is_allow', filter_var($filters['is_allow'], FILTER_VALIDATE_BOOL));
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $query->where(function ($query) use ($search): void {
            $query
                ->where('vehicles.name', 'ilike', "%{$search}%")
                ->orWhere('manufacturers.name', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('vehicles.ms_id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, Request $request): void
    {
        $sort = $request->string('sort')->toString();
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'vehicles.id',
            'ms_id' => 'vehicles.ms_id',
            'manufacturer' => 'manufacturers.name',
            'name' => 'vehicles.name',
            'generation_year_from' => 'vehicles.generation_year_from',
            'generation_year_to' => 'vehicles.generation_year_to',
            'type_carcase' => 'vehicles.type_carcase',
            'provider' => 'vehicles.provider',
            'is_allow' => 'vehicles.is_allow',
            default => 'vehicles.id',
        };

        $query->orderBy($column, $direction);
    }

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

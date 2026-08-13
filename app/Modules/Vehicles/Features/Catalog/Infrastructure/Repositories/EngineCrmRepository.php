<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPaginationMetaDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent adapter CRM read API двигателей.
 */
final readonly class EngineCrmRepository implements EngineCrmRepositoryInterface
{
    public function paginate(EngineCrmReadQueryDTO $query): EngineCrmPageDTO
    {
        $builder = $this->baseQuery();

        $this->applyFilters($builder, $query->filters);
        $this->applySearch($builder, $query->search);
        $this->applySort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        $items = collect($paginator->items())
            ->map(fn (Engine $engine): EngineCrmListItemDTO => $this->item($engine))
            ->values();

        return new EngineCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    public function findById(int $id): ?EngineCrmListItemDTO
    {
        $engine = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $engine === null ? null : $this->item($engine);
    }

    private function baseQuery(): Builder
    {
        return Engine::query()->withCount('modifications');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['provider', 'fuel_type', 'group_id'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn("engines.{$field}", $values);
        }

        if (isset($filters['code_engine']) && trim((string) $filters['code_engine']) !== '') {
            $query->where('engines.code_engine', 'ilike', '%'.trim((string) $filters['code_engine']).'%');
        }
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('engines.code_engine', 'ilike', "%{$search}%")
                ->orWhere('engines.engine_capacity', 'ilike', "%{$search}%")
                ->orWhere('engines.fuel_type', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query
                    ->orWhere('engines.id', (int) $search)
                    ->orWhere('engines.eng_id', (int) $search)
                    ->orWhere('engines.group_id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'engines.id',
            'eng_id' => 'engines.eng_id',
            'code_engine' => 'engines.code_engine',
            'engine_capacity' => 'engines.engine_capacity',
            'cylinder_count' => 'engines.cylinder_count',
            'fuel_type' => 'engines.fuel_type',
            'provider' => 'engines.provider',
            'group_id' => 'engines.group_id',
            'modifications_count' => 'modifications_count',
            default => 'engines.id',
        };

        $query->orderBy($column, $direction)->orderBy('engines.id');
    }

    private function item(Engine $engine): EngineCrmListItemDTO
    {
        return new EngineCrmListItemDTO(
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: $engine->code_engine === null ? null : (string) $engine->code_engine,
            engineCapacity: $engine->engine_capacity === null ? null : (string) $engine->engine_capacity,
            cylinderCount: $engine->cylinder_count === null ? null : (int) $engine->cylinder_count,
            cylinderDiameter: $engine->cylinder_diameter === null ? null : (float) $engine->cylinder_diameter,
            powerKwStart: $engine->power_kw_start === null ? null : (int) $engine->power_kw_start,
            powerKwUpto: $engine->power_kw_upto === null ? null : (int) $engine->power_kw_upto,
            powerPsStart: $engine->power_ps_start === null ? null : (int) $engine->power_ps_start,
            powerPsUpto: $engine->power_ps_upto === null ? null : (int) $engine->power_ps_upto,
            numberOfValves: $engine->number_of_valves === null ? null : (int) $engine->number_of_valves,
            fuelType: $engine->fuel_type?->value,
            groupId: $engine->group_id === null ? null : (int) $engine->group_id,
            provider: $this->enumValue($engine->provider, ProviderEnum::TD->value),
            allowChangeFields: $engine->allow_change_fields ?? [],
            modificationsCount: (int) $engine->modifications_count,
        );
    }

    private function meta(LengthAwarePaginator $paginator): EngineCrmPaginationMetaDTO
    {
        return new EngineCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }

    private function enumValue(?BackedEnum $enum, string $fallback): string
    {
        return $enum?->value ?? $fallback;
    }
}

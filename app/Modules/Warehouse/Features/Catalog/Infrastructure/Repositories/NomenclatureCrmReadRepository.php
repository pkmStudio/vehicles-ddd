<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmReadRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQL read adapter for CRM Warehouse nomenclature endpoints.
 */
final readonly class NomenclatureCrmReadRepository implements NomenclatureCrmReadRepositoryInterface
{
    private const int OPTION_LIMIT = 1000;

    private const array TEMPLATE_BY_CHAR = [
        'BP' => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        'SP' => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
        'WB' => NomenclatureDetailTemplateEnum::WIPER,
        'OF' => NomenclatureDetailTemplateEnum::OIL_FILTER,
        'AF' => NomenclatureDetailTemplateEnum::AIR_FILTER,
        'CF' => NomenclatureDetailTemplateEnum::CABIN_FILTER,
        'AW' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        'TB' => NomenclatureDetailTemplateEnum::TIMING_BELT,
        'VB' => NomenclatureDetailTemplateEnum::V_BELT,
        'HB' => NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING,
        'WH' => NomenclatureDetailTemplateEnum::WHEEL_HUB,
        'TE' => NomenclatureDetailTemplateEnum::TIE_ROD_END,
        'TR' => NomenclatureDetailTemplateEnum::TIE_ROD,
        'SL' => NomenclatureDetailTemplateEnum::STABILIZER_LINK,
        'BJ' => NomenclatureDetailTemplateEnum::BALL_JOINT,
        'CV' => NomenclatureDetailTemplateEnum::CV_JOINT,
        'SB' => NomenclatureDetailTemplateEnum::POLY_V_BELT,
    ];

    private const array TEMPLATE_BY_ID = [
        1 => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        2 => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
        3 => NomenclatureDetailTemplateEnum::WIPER,
        4 => NomenclatureDetailTemplateEnum::OIL_FILTER,
        5 => NomenclatureDetailTemplateEnum::AIR_FILTER,
        6 => NomenclatureDetailTemplateEnum::CABIN_FILTER,
        7 => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        8 => NomenclatureDetailTemplateEnum::TIMING_BELT,
        9 => NomenclatureDetailTemplateEnum::V_BELT,
        10 => NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING,
        11 => NomenclatureDetailTemplateEnum::WHEEL_HUB,
        12 => NomenclatureDetailTemplateEnum::TIE_ROD_END,
        13 => NomenclatureDetailTemplateEnum::TIE_ROD,
        14 => NomenclatureDetailTemplateEnum::STABILIZER_LINK,
        15 => NomenclatureDetailTemplateEnum::BALL_JOINT,
        16 => NomenclatureDetailTemplateEnum::CV_JOINT,
        17 => NomenclatureDetailTemplateEnum::POLY_V_BELT,
    ];

    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
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
            ->map(fn (object $nomenclature): NomenclatureCrmListItemDTO => $this->listItem($nomenclature))
            ->values();

        return new NomenclatureCrmPageDTO(
            data: $items,
            meta: new NomenclatureCrmPaginationMetaDTO(
                currentPage: $paginator->currentPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
                lastPage: $paginator->lastPage(),
            ),
        );
    }

    public function find(int $id): ?NomenclatureCrmListItemDTO
    {
        $nomenclature = $this->baseQuery()
            ->where('nomenclatures.id', $id)
            ->first();

        return $nomenclature === null ? null : $this->listItem($nomenclature);
    }

    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $builder = $this->baseQuery();
        $this->applySearch($builder, $query);

        return $builder
            ->orderBy('brands.name')
            ->orderBy('nomenclatures.part_number')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $nomenclature): NomenclatureCrmSearchItemDTO => new NomenclatureCrmSearchItemDTO(
                id: (int) $nomenclature->id,
                label: $this->label($nomenclature),
                partNumber: (string) $nomenclature->part_number,
            ))
            ->values();
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('types')
            ->select(['id', 'name', 'char'])
            ->orderBy('id');

        if ($id !== null) {
            $builder->where('id', $id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('char', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        return $builder
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map(fn (object $type): NomenclatureCrmOptionDTO => new NomenclatureCrmOptionDTO(
                id: (int) $type->id,
                label: (string) $type->name,
                meta: [
                    'char' => isset($type->char) ? (string) $type->char : null,
                    'template' => $this->templateValue($type),
                ],
            ))
            ->values();
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brandOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('brands')
            ->select(['id', 'name', 'char'])
            ->orderBy('name');

        if ($id !== null) {
            $builder->where('id', $id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('char', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        return $builder
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map(fn (object $brand): NomenclatureCrmOptionDTO => new NomenclatureCrmOptionDTO(
                id: (int) $brand->id,
                label: (string) $brand->name,
                meta: [
                    'char' => isset($brand->char) ? (string) $brand->char : null,
                ],
            ))
            ->values();
    }

    private function baseQuery(): Builder
    {
        return DB::table('nomenclatures')
            ->leftJoin('types', 'types.id', '=', 'nomenclatures.type_id')
            ->leftJoin('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->select([
                'nomenclatures.id',
                'nomenclatures.type_id',
                'types.name as type_name',
                'types.char as type_char',
                'nomenclatures.brand_id',
                'brands.name as brand_name',
                'brands.char as brand_char',
                'nomenclatures.name',
                'nomenclatures.country',
                'nomenclatures.part_number',
                'nomenclatures.color',
                'nomenclatures.weight',
                'nomenclatures.material',
                'nomenclatures.vehicle_type',
                'nomenclatures.quantity_pak',
                'nomenclatures.quantity_in_pak',
                'nomenclatures.details',
                'nomenclatures.created_at',
                'nomenclatures.updated_at',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['type_id', 'brand_id'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn("nomenclatures.{$field}", $values);
        }

        foreach (['name', 'country', 'part_number'] as $field) {
            if (! isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                continue;
            }

            $query->where("nomenclatures.{$field}", 'ilike', '%'.trim((string) $filters[$field]).'%');
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
                ->where('nomenclatures.name', 'ilike', "%{$search}%")
                ->orWhere('nomenclatures.part_number', 'ilike', "%{$search}%")
                ->orWhere('nomenclatures.country', 'ilike', "%{$search}%")
                ->orWhere('types.name', 'ilike', "%{$search}%")
                ->orWhere('brands.name', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('nomenclatures.id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'nomenclatures.id',
            'name' => 'nomenclatures.name',
            'country' => 'nomenclatures.country',
            'part_number' => 'nomenclatures.part_number',
            'type_name' => 'types.name',
            'brand_name' => 'brands.name',
            'weight' => 'nomenclatures.weight',
            'quantity_pak' => 'nomenclatures.quantity_pak',
            'quantity_in_pak' => 'nomenclatures.quantity_in_pak',
            default => 'nomenclatures.id',
        };

        $query->orderBy($column, $direction);
    }

    private function listItem(object $nomenclature): NomenclatureCrmListItemDTO
    {
        return new NomenclatureCrmListItemDTO(
            id: (int) $nomenclature->id,
            typeId: (int) $nomenclature->type_id,
            typeName: isset($nomenclature->type_name) ? (string) $nomenclature->type_name : null,
            typeChar: isset($nomenclature->type_char) ? (string) $nomenclature->type_char : null,
            typeTemplate: $this->templateValue($nomenclature),
            brandId: (int) $nomenclature->brand_id,
            brandName: isset($nomenclature->brand_name) ? (string) $nomenclature->brand_name : null,
            brandChar: isset($nomenclature->brand_char) ? (string) $nomenclature->brand_char : null,
            name: (string) $nomenclature->name,
            country: (string) $nomenclature->country,
            partNumber: (string) $nomenclature->part_number,
            color: (string) $nomenclature->color,
            weight: (int) $nomenclature->weight,
            material: $this->listStringArray($nomenclature->material),
            vehicleType: $this->listStringArray($nomenclature->vehicle_type),
            quantityPak: (int) $nomenclature->quantity_pak,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: $this->jsonArray($nomenclature->details),
            createdAt: isset($nomenclature->created_at) ? (string) $nomenclature->created_at : null,
            updatedAt: isset($nomenclature->updated_at) ? (string) $nomenclature->updated_at : null,
        );
    }

    private function label(object $nomenclature): string
    {
        return trim(sprintf(
            '%s | %s | %s | %s',
            $nomenclature->id,
            $nomenclature->part_number,
            $nomenclature->brand_name,
            $nomenclature->name,
        ));
    }

    private function templateValue(object $type): ?string
    {
        $template = $this->template($type);

        return $template?->value;
    }

    private function template(object $type): ?NomenclatureDetailTemplateEnum
    {
        $char = isset($type->type_char)
            ? (string) $type->type_char
            : (isset($type->char) ? (string) $type->char : null);

        if ($char !== null && isset(self::TEMPLATE_BY_CHAR[$char])) {
            return self::TEMPLATE_BY_CHAR[$char];
        }

        $id = isset($type->type_id)
            ? (int) $type->type_id
            : (int) $type->id;

        return self::TEMPLATE_BY_ID[$id] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function listStringArray(mixed $value): array
    {
        $decoded = $this->jsonArray($value);

        return array_values(array_map('strval', $decoded));
    }
}

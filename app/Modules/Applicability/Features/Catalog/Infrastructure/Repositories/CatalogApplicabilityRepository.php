<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityEvidenceDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclatureDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclaturePageDTO;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Читает положительные факты применяемости для публичного каталога dan-catalog.
 */
final readonly class CatalogApplicabilityRepository implements CatalogApplicabilityRepositoryInterface
{
    /** Возвращает канонический артикул выбранного бренда без учёта регистра. */
    public function findPartNumber(string $partNumber, int $brandId): ?string
    {
        $canonicalPartNumber = DB::table('nomenclatures')
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(part_number) = ?', [mb_strtolower($partNumber)])
            ->value('part_number');

        return is_string($canonicalPartNumber) ? $canonicalPartNumber : null;
    }

    /** Проверяет существование модификации по внутреннему primary key. */
    public function modificationExists(int $modificationId): bool
    {
        return DB::table('modifications')
            ->where('id', $modificationId)
            ->exists();
    }

    /**
     * Читает дедуплицированные подтверждения применяемости через активные комплекты.
     *
     * @return Collection<int, ApplicabilityEvidenceDTO>
     */
    public function evidence(string $partNumber, int $modificationId, int $brandId): Collection
    {
        return $this->baseApplicabilityQuery($modificationId, $brandId)
            ->whereRaw('LOWER(nomenclatures.part_number) = ?', [mb_strtolower($partNumber)])
            ->distinct()
            ->orderBy('applicability.kit_id')
            ->orderBy('applicability.target_type')
            ->get([
                'applicability.kit_id',
                'applicability.target_type',
                'applicability.source',
                'applicability.algorithm',
            ])
            ->map(static fn (object $evidence): ApplicabilityEvidenceDTO => new ApplicabilityEvidenceDTO(
                kitId: (int) $evidence->kit_id,
                targetType: (string) $evidence->target_type,
                source: (string) $evidence->source,
                algorithm: is_string($evidence->algorithm) ? $evidence->algorithm : null,
            ))
            ->values();
    }

    /**
     * Возвращает категории, содержащие применимые товары выбранного бренда.
     *
     * @return Collection<int, ApplicableCategoryDTO>
     */
    public function categories(int $modificationId, int $brandId): Collection
    {
        return $this->baseApplicabilityQuery($modificationId, $brandId)
            ->join('types', 'types.id', '=', 'nomenclatures.type_id')
            ->groupBy('types.id', 'types.name', 'types.char')
            ->orderBy('types.name')
            ->get([
                'types.id',
                'types.name',
                'types.char',
                DB::raw('COUNT(DISTINCT nomenclatures.id) as nomenclature_count'),
            ])
            ->map(static fn (object $category): ApplicableCategoryDTO => new ApplicableCategoryDTO(
                id: (int) $category->id,
                name: (string) $category->name,
                code: is_string($category->char) ? $category->char : null,
                nomenclatureCount: (int) $category->nomenclature_count,
            ))
            ->values();
    }

    /** Возвращает страницу distinct применимых товаров категории. */
    public function paginateNomenclatures(
        int $modificationId,
        int $categoryId,
        int $brandId,
        int $page,
        int $pageSize,
    ): ?ApplicableNomenclaturePageDTO {
        $category = $this->categories($modificationId, $brandId)
            ->first(static fn (ApplicableCategoryDTO $item): bool => $item->id === $categoryId);

        if (! $category instanceof ApplicableCategoryDTO) {
            return null;
        }

        $total = $category->nomenclatureCount;
        $items = $this->baseApplicabilityQuery($modificationId, $brandId)
            ->join('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->where('nomenclatures.type_id', $categoryId)
            ->distinct()
            ->orderBy('nomenclatures.name')
            ->orderBy('nomenclatures.part_number')
            ->forPage($page, $pageSize)
            ->get([
                'nomenclatures.part_number',
                'nomenclatures.name',
                'nomenclatures.type_id',
                'nomenclatures.brand_id',
                'brands.name as brand_name',
            ])
            ->map(static fn (object $item): ApplicableNomenclatureDTO => new ApplicableNomenclatureDTO(
                partNumber: (string) $item->part_number,
                name: (string) $item->name,
                categoryId: (int) $item->type_id,
                brandId: (int) $item->brand_id,
                brandName: (string) $item->brand_name,
            ))
            ->values();

        return new ApplicableNomenclaturePageDTO(
            category: $category,
            items: $items,
            total: $total,
            page: $page,
            pageSize: $pageSize,
            pageCount: (int) ceil($total / $pageSize),
        );
    }

    /**
     * Строит общий query товара через состав активного комплекта к положительному факту.
     */
    private function baseApplicabilityQuery(int $modificationId, int $brandId): Builder
    {
        $query = DB::table('kit_applicabilities as applicability')
            ->join('kits', 'kits.id', '=', 'applicability.kit_id')
            ->join('kit_nomenclature', 'kit_nomenclature.kit_id', '=', 'kits.id')
            ->join('nomenclatures', 'nomenclatures.id', '=', 'kit_nomenclature.nomenclature_id')
            ->where('kits.is_active', true)
            ->where('nomenclatures.brand_id', $brandId);

        $this->whereAppliesToModification($query, $modificationId);

        return $query;
    }

    /**
     * Учитывает прямую, engine и part_specification цели выбранной модификации.
     */
    private function whereAppliesToModification(Builder $query, int $modificationId): void
    {
        $query->where(function (Builder $targets) use ($modificationId): void {
            $targets
                ->where(function (Builder $direct) use ($modificationId): void {
                    $direct
                        ->where('applicability.target_type', ApplicabilityTargetTypeEnum::MODIFICATION->value)
                        ->where('applicability.target_id', $modificationId);
                })
                ->orWhere(function (Builder $engine) use ($modificationId): void {
                    $engine
                        ->where('applicability.target_type', ApplicabilityTargetTypeEnum::ENGINE->value)
                        ->whereExists(function (Builder $engineModification) use ($modificationId): void {
                            $engineModification
                                ->selectRaw('1')
                                ->from('engine_modification')
                                ->whereColumn('engine_modification.engine_id', 'applicability.target_id')
                                ->where('engine_modification.modification_id', $modificationId);
                        });
                })
                ->orWhere(function (Builder $specification) use ($modificationId): void {
                    $specification
                        ->where('applicability.target_type', ApplicabilityTargetTypeEnum::PART_SPECIFICATION->value)
                        ->whereExists(function (Builder $partSpecification) use ($modificationId): void {
                            $partSpecification
                                ->selectRaw('1')
                                ->from('part_specifications')
                                ->whereColumn('part_specifications.id', 'applicability.target_id')
                                ->where(function (Builder $owner) use ($modificationId): void {
                                    $owner
                                        ->where(function (Builder $vehicle) use ($modificationId): void {
                                            $vehicle
                                                ->where('part_specifications.partable_type', PartableTypeEnum::VEHICLE->value)
                                                ->whereExists(function (Builder $modification) use ($modificationId): void {
                                                    $modification
                                                        ->selectRaw('1')
                                                        ->from('modifications')
                                                        ->where('modifications.id', $modificationId)
                                                        ->whereColumn('modifications.vehicle_id', 'part_specifications.partable_id');
                                                });
                                        })
                                        ->orWhere(function (Builder $engineOwner) use ($modificationId): void {
                                            $engineOwner
                                                ->where('part_specifications.partable_type', PartableTypeEnum::ENGINE->value)
                                                ->whereExists(function (Builder $engineModification) use ($modificationId): void {
                                                    $engineModification
                                                        ->selectRaw('1')
                                                        ->from('engine_modification')
                                                        ->whereColumn('engine_modification.engine_id', 'part_specifications.partable_id')
                                                        ->where('engine_modification.modification_id', $modificationId);
                                                });
                                        });
                                });
                        });
                });
        });
    }
}

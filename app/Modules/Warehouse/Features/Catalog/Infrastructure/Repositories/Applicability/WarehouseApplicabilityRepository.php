<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\Applicability;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseNomenclatureForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseTypeForApplicabilityDTO;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class WarehouseApplicabilityRepository implements WarehouseApplicabilityRepositoryInterface
{
    public function __construct(
        private NomenclatureCrmTypeTemplateResolver $templateResolver,
    ) {}

    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        $query = DB::table('kits')->where('is_active', true);

        if ($kitId !== null) {
            $query->where('id', $kitId);
        }

        foreach ($query->lazyById($chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    public function kitExists(int $kitId): bool
    {
        return DB::table('kits')->where('id', $kitId)->exists();
    }

    private function mapKit(stdClass $kit): WarehouseKitForApplicabilityDTO
    {
        return new WarehouseKitForApplicabilityDTO(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $this->nomenclatures((int) $kit->id),
            type: $this->type((int) $kit->type_id),
        );
    }

    /**
     * @return array<int, WarehouseNomenclatureForApplicabilityDTO>
     */
    private function nomenclatures(int $kitId): array
    {
        return DB::table('nomenclatures')
            ->join('kit_nomenclature', 'kit_nomenclature.nomenclature_id', '=', 'nomenclatures.id')
            ->where('kit_nomenclature.kit_id', $kitId)
            ->orderBy('kit_nomenclature.sort')
            ->select([
                'nomenclatures.id',
                'nomenclatures.type_id',
                'nomenclatures.quantity_in_pak',
                'nomenclatures.details',
                'kit_nomenclature.sort',
            ])
            ->get()
            ->map(fn (stdClass $nomenclature): WarehouseNomenclatureForApplicabilityDTO => new WarehouseNomenclatureForApplicabilityDTO(
                id: (int) $nomenclature->id,
                typeId: (int) $nomenclature->type_id,
                quantityInPak: (int) $nomenclature->quantity_in_pak,
                details: $this->jsonArray($nomenclature->details),
                sort: (int) $nomenclature->sort,
                type: $this->type((int) $nomenclature->type_id),
            ))
            ->all();
    }

    private function type(int $typeId): ?WarehouseTypeForApplicabilityDTO
    {
        $type = DB::table('types')->where('id', $typeId)->first(['id', 'name', 'char']);

        if ($type === null) {
            return null;
        }

        return new WarehouseTypeForApplicabilityDTO(
            id: (int) $type->id,
            name: (string) $type->name,
            char: $type->char === null ? null : (string) $type->char,
            template: $this->templateResolver->resolve($type),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

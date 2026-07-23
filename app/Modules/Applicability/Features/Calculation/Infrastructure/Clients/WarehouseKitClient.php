<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\TypeData;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Kit;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Nomenclature;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Type;

/**
 * Читает активные Warehouse-наборы и переводит их в calculation-снимки.
 */
final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    public function __construct(
        private TypeTemplateResolverInterface $templates,
    ) {}

    /**
     * Возвращает активные наборы ленивым потоком, чтобы расчет не грузил все строки в память.
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        $query = Kit::query()
            ->with(['type', 'nomenclatures.type'])
            ->where('is_active', true);

        if ($kitId !== null) {
            $query->where('id', $kitId);
        }

        foreach ($query->lazyById($chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    private function mapKit(Kit $kit): KitData
    {
        $type = $this->mapType($kit->type);
        $toNomenclatureData = fn (Nomenclature $nomenclature): NomenclatureData => $this->mapNomenclature(
            $nomenclature,
        );
        $nomenclatures = $kit->nomenclatures
            ->map($toNomenclatureData)
            ->all();

        return new KitData(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $nomenclatures,
            type: $type,
            template: $type?->template,
        );
    }

    private function mapNomenclature(Nomenclature $nomenclature): NomenclatureData
    {
        $type = $this->mapType($nomenclature->type);

        return new NomenclatureData(
            typeId: (int) $nomenclature->type_id,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: (array) ($nomenclature->details ?? []),
            id: (int) $nomenclature->id,
            sort: (int) ($nomenclature->pivot?->sort ?? 0),
            type: $type,
            template: $type?->template,
        );
    }

    private function mapType(?Type $type): ?TypeData
    {
        if ($type === null) {
            return null;
        }

        $typeData = new TypeData(
            name: (string) $type->name,
            char: $type->char,
            id: (int) $type->id,
        );

        return new TypeData(
            name: $typeData->name,
            char: $typeData->char,
            id: $typeData->id,
            template: $this->templates->resolve($typeData),
        );
    }
}

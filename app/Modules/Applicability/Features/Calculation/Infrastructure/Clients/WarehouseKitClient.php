<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface as PublicWarehouseApplicabilityClientInterface;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseNomenclatureForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseTypeForApplicabilityDTO;

/**
 * Читает активные Warehouse-наборы и переводит их в calculation-снимки.
 */
final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    /**
     * Получает публичный Warehouse applicability client.
     *
     * Шаги:
     * 1. Сохраняет read-only Warehouse boundary.
     * 2. Оставляет ленивое чтение и mapping active kits методу `activeKits()`.
     */
    public function __construct(
        private PublicWarehouseApplicabilityClientInterface $warehouse,
    ) {}

    /**
     * Возвращает активные наборы ленивым потоком, чтобы расчет не грузил все строки в память.
     *
     * Шаги:
     * 1. Запрашивает active applicability kits во внешнем Warehouse boundary.
     * 2. Итерирует результат как stream с optional kit filter и chunk size.
     * 3. Для каждого kit возвращает локальный `KitData`.
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        foreach ($this->warehouse->activeApplicabilityKits($kitId, $chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    private function mapKit(WarehouseKitForApplicabilityDTO $kit): KitData
    {
        $type = $this->mapType($kit->type);
        $nomenclatures = array_map(
            fn (WarehouseNomenclatureForApplicabilityDTO $nomenclature): NomenclatureData => $this->mapNomenclature($nomenclature),
            $kit->nomenclatures,
        );

        return new KitData(
            id: $kit->id,
            typeId: $kit->typeId,
            quantityInPackage: $kit->quantityInPackage,
            isActive: $kit->isActive,
            nomenclatures: $nomenclatures,
            type: $type,
            template: $type?->template,
        );
    }

    private function mapNomenclature(WarehouseNomenclatureForApplicabilityDTO $nomenclature): NomenclatureData
    {
        $type = $this->mapType($nomenclature->type);

        return new NomenclatureData(
            typeId: $nomenclature->typeId,
            quantityInPak: $nomenclature->quantityInPak,
            details: $nomenclature->details,
            id: $nomenclature->id,
            sort: $nomenclature->sort,
            type: $type,
            template: $type?->template,
        );
    }

    private function mapType(?WarehouseTypeForApplicabilityDTO $type): ?TypeData
    {
        if ($type === null) {
            return null;
        }

        return new TypeData(
            name: $type->name,
            char: $type->char,
            id: $type->id,
            template: $type->template,
        );
    }
}

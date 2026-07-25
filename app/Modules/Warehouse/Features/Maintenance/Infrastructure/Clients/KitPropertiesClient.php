<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Infrastructure\Clients;

use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;
use App\Modules\Warehouse\Features\Maintenance\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Maintenance\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\Nomenclature;

final readonly class KitPropertiesClient implements KitPropertiesClientInterface
{
    public function __construct(
        private KitPropertiesServiceInterface $kitProperties,
    ) {}

    public function build(array $nomenclatures): KitPropertiesDTO
    {
        $properties = $this->kitProperties->build(array_map(
            fn (Nomenclature $nomenclature): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($nomenclature),
            $nomenclatures,
        ));

        return new KitPropertiesDTO(
            typeId: $properties->typeId,
            packDimensionId: $properties->packDimensionId,
            weight: $properties->weight,
            quantityInPackage: $properties->quantityInPackage,
            quantityPackage: $properties->quantityPackage,
            complectation: $properties->complectation,
            importHash: $properties->importHash,
        );
    }

    private function toKitPropertiesNomenclature(Nomenclature $nomenclature): KitPropertiesNomenclatureData
    {
        $type = $nomenclature->type === null
            ? null
            : new KitPropertiesTypeData(
                name: $nomenclature->type->name,
                char: $nomenclature->type->char,
                id: $nomenclature->type->id,
            );

        return new KitPropertiesNomenclatureData(
            typeId: $nomenclature->type_id,
            partNumber: $nomenclature->part_number,
            quantityInPak: $nomenclature->quantity_in_pak,
            quantityPak: $nomenclature->quantity_pak,
            weight: $nomenclature->weight,
            material: $nomenclature->material,
            details: $nomenclature->details,
            id: $nomenclature->id,
            type: $type,
            brandId: $nomenclature->brand_id,
        );
    }
}

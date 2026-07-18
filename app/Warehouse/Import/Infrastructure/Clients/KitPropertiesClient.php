<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Clients;

use App\Warehouse\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Warehouse\Import\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Warehouse\Import\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;

final readonly class KitPropertiesClient implements KitPropertiesClientInterface
{
    public function __construct(
        private KitPropertiesServiceInterface $kitProperties,
    ) {}

    public function build(array $nomenclatures): KitPropertiesDTO
    {
        $properties = $this->kitProperties->build(array_map(
            fn (NomenclatureData $nomenclature): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($nomenclature),
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

    private function toKitPropertiesNomenclature(NomenclatureData $nomenclature): KitPropertiesNomenclatureData
    {
        $type = $nomenclature->type === null
            ? null
            : new KitPropertiesTypeData(
                name: $nomenclature->type->name,
                char: $nomenclature->type->char,
                id: $nomenclature->type->id,
            );

        return new KitPropertiesNomenclatureData(
            typeId: $nomenclature->typeId,
            partNumber: $nomenclature->partNumber,
            quantityInPak: $nomenclature->quantityInPak,
            quantityPak: $nomenclature->quantityPak,
            weight: $nomenclature->weight,
            material: $nomenclature->material,
            details: $nomenclature->details,
            id: $nomenclature->id,
            type: $type,
        );
    }
}

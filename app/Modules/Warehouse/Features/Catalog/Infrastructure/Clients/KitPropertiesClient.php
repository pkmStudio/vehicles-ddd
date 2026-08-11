<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Exceptions\KitPropertiesCompositionException;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;

final readonly class KitPropertiesClient implements KitPropertiesClientInterface
{
    public function __construct(
        private KitPropertiesServiceInterface $kitProperties,
    ) {}

    public function build(array $nomenclatures): KitPropertiesDTO
    {
        $toKitPropertiesNomenclature = fn (NomenclatureData $nomenclature): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($nomenclature);

        try {
            $properties = $this->kitProperties->build(array_map(
                $toKitPropertiesNomenclature,
                $nomenclatures,
            ));
        } catch (KitCompositionException $e) {
            throw new KitPropertiesCompositionException($e->getMessage(), previous: $e);
        }

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
            brandId: $nomenclature->brandId,
        );
    }
}

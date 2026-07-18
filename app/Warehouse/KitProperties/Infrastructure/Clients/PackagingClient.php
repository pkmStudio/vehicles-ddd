<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Infrastructure\Clients;

use App\Warehouse\KitProperties\Domain\Contracts\Clients\PackagingClientInterface;
use App\Warehouse\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Warehouse\KitProperties\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData as PackagingNomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData as PackagingTypeData;
use App\Warehouse\Packaging\Domain\Exceptions\PackDimensionNotResolvableException as PackagingPackDimensionNotResolvableException;

final readonly class PackagingClient implements PackagingClientInterface
{
    public function __construct(
        private PackagingServiceInterface $packaging,
    ) {}

    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionDTO
    {
        try {
            $packDimension = $this->packaging->selectOrCreate(
                type: new PackagingTypeData(
                    name: $type->name,
                    char: $type->char,
                    id: $type->id,
                ),
                nomenclatures: array_map(
                    fn (NomenclatureData $nomenclature): PackagingNomenclatureData => new PackagingNomenclatureData(
                        partNumber: $nomenclature->partNumber,
                        quantityInPak: $nomenclature->quantityInPak,
                        details: $nomenclature->details,
                        id: $nomenclature->id,
                    ),
                    $nomenclatures,
                ),
            );
        } catch (PackagingPackDimensionNotResolvableException $exception) {
            throw new PackDimensionNotResolvableException($exception->getMessage(), previous: $exception);
        }

        return new PackDimensionDTO(
            name: $packDimension->name,
            weight: $packDimension->weight,
            width: $packDimension->width,
            height: $packDimension->height,
            length: $packDimension->length,
            price: $packDimension->price,
            typeId: $packDimension->typeId,
            generated: $packDimension->generated,
            id: $packDimension->id,
        );
    }
}

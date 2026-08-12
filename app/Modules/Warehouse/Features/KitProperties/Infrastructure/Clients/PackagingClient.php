<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Infrastructure\Clients;

use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients\PackagingClientInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Exceptions\PackDimensionNotResolvableException as PackagingPackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData as PackagingNomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData as PackagingTypeData;

final readonly class PackagingClient implements PackagingClientInterface
{
    /**
     * Получает service port Packaging-фичи для подбора или создания упаковки.
     * Шаги:
     * 1) Сохранить PackagingServiceInterface как единственную точку синхронного вызова соседней фичи.
     * 2) Оставить преобразование DTO на стороне infrastructure adapter-а KitProperties.
     */
    public function __construct(
        private PackagingServiceInterface $packaging,
    ) {}

    /**
     * Подбирает или создаёт упаковку через Packaging и возвращает локальный DTO KitProperties.
     * Шаги:
     * 1) Перевести TypeData KitProperties в TypeData Packaging.
     * 2) Перевести каждую локальную NomenclatureData в DTO Packaging-фичи.
     * 3) Вызвать PackagingServiceInterface::selectOrCreate().
     * 4) Ошибку Packaging PackDimensionNotResolvableException завернуть в локальное исключение.
     * 5) Перевести результат Packaging обратно в PackDimensionDTO KitProperties.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionDTO
    {
        try {
            $packagingType = new PackagingTypeData(
                name: $type->name,
                char: $type->char,
                id: $type->id,
            );
            $toPackagingNomenclature = fn (NomenclatureData $nomenclature): PackagingNomenclatureData => new PackagingNomenclatureData(
                partNumber: $nomenclature->partNumber,
                quantityInPak: $nomenclature->quantityInPak,
                details: $nomenclature->details,
                id: $nomenclature->id,
            );
            $packagingNomenclatures = array_map($toPackagingNomenclature, $nomenclatures);

            $packDimension = $this->packaging->selectOrCreate(
                type: $packagingType,
                nomenclatures: $packagingNomenclatures,
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

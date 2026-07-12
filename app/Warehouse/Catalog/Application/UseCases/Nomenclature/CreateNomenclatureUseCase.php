<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\CreateNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;
use Throwable;

/**
 * Выполняет создание Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class CreateNomenclatureUseCase implements CreateNomenclatureUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private BrandRepositoryInterface $brands,
        private TypeRepositoryInterface $types,
        private NomenclatureCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Создаёт номенклатуру, если type/brand существуют и артикул свободен.
     */
    public function execute(CreateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->types->find($request->typeId) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::TypeNotFound,
                    businessKey: $request->partNumber,
                );
            }

            if ($this->brands->find($request->brandId) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::BrandNotFound,
                    businessKey: $request->partNumber,
                );
            }

            if ($this->nomenclatures->firstByPartNumber($request->partNumber) !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::AlreadyExists,
                    businessKey: $request->partNumber,
                );
            }

            $data = $this->data($request);
            $nomenclature = $this->command->create($data);

            event(new NomenclatureCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                nomenclature: $nomenclature,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                recordId: $nomenclature->id,
                businessKey: $nomenclature->partNumber,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                businessKey: $request->partNumber,
            );

            throw $e;
        }
    }

    /**
     * Собирает Data-снимок номенклатуры для Command.
     */
    private function data(CreateNomenclatureRequestDTO $request): NomenclatureData
    {
        return new NomenclatureData(
            typeId: $request->typeId,
            brandId: $request->brandId,
            name: $request->name,
            country: $request->country,
            partNumber: $request->partNumber,
            color: $request->color,
            weight: $request->weight,
            material: $request->material,
            vehicleType: $request->vehicleType,
            quantityPak: $request->quantityPak,
            quantityInPak: $request->quantityInPak,
            details: $request->details,
        );
    }
}

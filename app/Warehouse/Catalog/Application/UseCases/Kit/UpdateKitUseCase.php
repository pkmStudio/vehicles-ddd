<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Kit;

use App\Warehouse\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\UpdateKitUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\Kit\KitUpdated;
use App\Warehouse\Catalog\Domain\ModelData\KitData;
use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\DTOs\KitPropertiesDTO;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData as KitPropertiesNomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData as KitPropertiesTypeData;
use App\Warehouse\Packaging\Domain\Exceptions\PackDimensionNotResolvableException;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

/**
 * Выполняет обновление Warehouse-набора из внешнего сообщения.
 */
final readonly class UpdateKitUseCase implements UpdateKitUseCaseInterface
{
    /**
     * Инициализирует чтение номенклатуры/наборов, расчёт свойств, запись, cache и result-сервис.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private KitPropertiesServiceInterface $kitProperties,
        private KitCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Обновляет набор из состава номенклатур, пересчитывая производные свойства через KitProperties.
     */
    public function execute(UpdateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->kits->find($request->id) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            $ordered = $this->orderedNomenclatures($request->nomenclatureIds);
            if ($ordered['missing_ids'] !== []) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NomenclatureNotFound,
                    errors: ['missing_ids' => $ordered['missing_ids']],
                    recordId: $request->id,
                );
            }

            $properties = $this->buildProperties(
                request: $request,
                nomenclatures: $ordered['items'],
            );
            if ($properties instanceof WarehouseCatalogMutationResultDTO) {
                return $properties;
            }

            if ($properties->packDimensionId === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::PackDimensionNotResolvable,
                    recordId: $request->id,
                    businessKey: $properties->importHash,
                );
            }

            $duplicate = $this->kits->firstByImportHash($properties->importHash);
            if ($duplicate !== null && $duplicate->id !== $request->id) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::AlreadyExists,
                    recordId: $duplicate->id,
                    businessKey: $properties->importHash,
                );
            }

            $data = $this->data(
                request: $request,
                properties: $properties,
                count: count($ordered['items']),
            );
            $kit = $this->command->update(
                data: $data,
                nomenclatureIds: $request->nomenclatureIds,
            );

            event(new KitUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                kit: $kit,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $kit->id,
                businessKey: $kit->importHash,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $request->id,
            );

            throw $e;
        }
    }

    /**
     * Резолвит номенклатуры по id, сохраняя порядок входящего payload.
     *
     * @param  array<int, int>  $ids
     * @return array{items: array<int, NomenclatureData>, missing_ids: array<int, int>}
     */
    private function orderedNomenclatures(array $ids): array
    {
        $found = $this->nomenclatures->findByIds($ids);
        $items = [];
        $missingIds = [];

        foreach ($ids as $id) {
            $nomenclature = $found->get($id);
            if ($nomenclature === null) {
                $missingIds[] = $id;

                continue;
            }
            $items[] = $nomenclature;
        }

        return [
            'items' => $items,
            'missing_ids' => $missingIds,
        ];
    }

    /**
     * Рассчитывает свойства набора или возвращает rejected-результат при бизнес-ошибке состава.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    private function buildProperties(
        UpdateKitRequestDTO $request,
        array $nomenclatures,
    ): KitPropertiesDTO|WarehouseCatalogMutationResultDTO {
        try {
            return $this->kitProperties->build(array_map(
                fn (NomenclatureData $nomenclature): KitPropertiesNomenclatureData => $this->toKitPropertiesNomenclature($nomenclature),
                $nomenclatures,
            ));
        } catch (PackDimensionNotResolvableException $e) {
            return $this->results->rejected(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                reason: WarehouseCatalogMutationRejectReasonEnum::PackDimensionNotResolvable,
                errors: ['message' => $e->getMessage()],
                recordId: $request->id,
            );
        } catch (InvalidArgumentException|UnexpectedValueException $e) {
            return $this->results->rejected(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                reason: WarehouseCatalogMutationRejectReasonEnum::InvalidComposition,
                errors: ['message' => $e->getMessage()],
                recordId: $request->id,
            );
        }
    }

    /**
     * Переводит Catalog-снимок номенклатуры в DTO фичи KitProperties.
     */
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

    /**
     * Собирает Data-снимок набора для Command.
     */
    private function data(UpdateKitRequestDTO $request, KitPropertiesDTO $properties, int $count): KitData
    {
        return new KitData(
            complectation: $properties->complectation,
            guarantee: $request->guarantee,
            quantityInPackage: $properties->quantityInPackage,
            quantityPackage: $properties->quantityPackage,
            complement: $count > 1,
            weight: (int) round($properties->weight),
            packDimensionId: (int) $properties->packDimensionId,
            typeId: $properties->typeId,
            importHash: $properties->importHash,
            isSaleSeparately: $request->isSaleSeparately,
            isActive: $request->isActive,
            id: $request->id,
        );
    }
}

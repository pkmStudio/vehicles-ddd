<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\UpdateNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\Nomenclature\NomenclatureUpdated;
use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;
use Throwable;

/**
 * Выполняет обновление Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class UpdateNomenclatureUseCase implements UpdateNomenclatureUseCaseInterface
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
     * Обновляет номенклатуру, если запись существует, type/brand существуют и артикул не занят.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил обновление дважды.
     * 2) Проверить существование записи, type/brand и отсутствие конфликта по артикулу.
     * 3) Собрать NomenclatureData с identity, обновить запись через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(UpdateNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->nomenclatures->find($request->id) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                    businessKey: $request->partNumber,
                );
            }

            if ($this->types->find($request->typeId) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::TypeNotFound,
                    recordId: $request->id,
                    businessKey: $request->partNumber,
                );
            }

            if ($this->brands->find($request->brandId) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::BrandNotFound,
                    recordId: $request->id,
                    businessKey: $request->partNumber,
                );
            }

            if ($this->nomenclatures->partNumberExistsForAnother($request->partNumber, $request->id)) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::AlreadyExists,
                    recordId: $request->id,
                    businessKey: $request->partNumber,
                );
            }

            $data = $this->data($request);
            $nomenclature = $this->command->update($data);

            event(new NomenclatureUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                nomenclature: $nomenclature,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $nomenclature->id,
                businessKey: $nomenclature->partNumber,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $request->id,
                businessKey: $request->partNumber,
            );

            throw $e;
        }
    }

    /**
     * Собирает Data-снимок номенклатуры для Command.
     */
    private function data(UpdateNomenclatureRequestDTO $request): NomenclatureData
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
            id: $request->id,
        );
    }
}

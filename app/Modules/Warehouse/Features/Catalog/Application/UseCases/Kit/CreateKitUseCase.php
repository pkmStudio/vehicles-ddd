<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\CreateKitUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Shared\Domain\Events\Kit\KitCreated;
use Throwable;

/**
 * Выполняет создание Warehouse-набора из внешнего сообщения.
 */
final readonly class CreateKitUseCase implements CreateKitUseCaseInterface
{
    /**
     * Инициализирует чтение номенклатуры, расчёт свойств, запись, cache и result-сервис.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private KitPropertiesClientInterface $kitProperties,
        private KitCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Создаёт набор из состава номенклатур, рассчитывая производные свойства через KitProperties.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не создал дубль.
     * 2) Резолвить номенклатуры состава и рассчитать производные свойства через KitProperties.
     * 3) Проверить упаковку и отсутствие дубля по import_hash.
     * 4) Собрать KitData, записать набор через Command и отправить доменный факт.
     * 5) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(CreateKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $ordered = $this->orderedNomenclatures($request->nomenclatureIds);
            if ($ordered['missing_ids'] !== []) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NomenclatureNotFound,
                    errors: ['missing_ids' => $ordered['missing_ids']],
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
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::PackDimensionNotResolvable,
                    businessKey: $properties->importHash,
                );
            }

            $duplicate = $this->kits->findByImportHash($properties->importHash);
            if ($duplicate !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
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
            $kit = $this->command->create(
                data: $data,
                nomenclatureIds: $request->nomenclatureIds,
            );

            event(new KitCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                kit: $kit->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                recordId: $kit->id,
                businessKey: $kit->importHash,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Create,
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
        CreateKitRequestDTO $request,
        array $nomenclatures,
    ): KitPropertiesDTO|WarehouseCatalogMutationResultDTO {
        try {
            return $this->kitProperties->build($nomenclatures);
        } catch (KitCompositionException $e) {
            return $this->results->rejected(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                reason: WarehouseCatalogMutationRejectReasonEnum::InvalidComposition,
                errors: ['message' => $e->getMessage()],
            );
        }
    }

    /**
     * Собирает Data-снимок набора для Command.
     */
    private function data(CreateKitRequestDTO $request, KitPropertiesDTO $properties, int $count): KitData
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
        );
    }
}

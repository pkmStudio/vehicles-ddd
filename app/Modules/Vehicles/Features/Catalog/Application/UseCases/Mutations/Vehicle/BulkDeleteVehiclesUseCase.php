<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications\VehicleBulkDeleteNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleBulkDeleteErrorDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleBulkDeleteRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleBulkDeleteResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleDeleted;
use Throwable;

/**
 * Выполняет массовое удаление автомобилей с единым result event.
 */
final readonly class BulkDeleteVehiclesUseCase
{
    /**
     * Получает порты чтения, каскадного удаления, идемпотентности и публикации результата.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private CatalogCascadeDeleteServiceInterface $cascade,
        private CatalogMutationCacheServiceInterface $cache,
        private VehicleBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет OD-автомобили по ms_id, TD и отсутствующие строки возвращает в counters/errors.
     */
    public function execute(VehicleBulkDeleteRequestDTO $request): ?VehicleBulkDeleteResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $msIds = $this->uniqueIds($request->msIds);
            $vehicles = $this->vehicles->findByMsIds($msIds);
            $deleteIds = [];
            $errors = [];
            $skipped = 0;
            $failed = 0;

            foreach ($msIds as $msId) {
                $vehicle = $vehicles->get($msId);
                if (! $vehicle instanceof VehicleData) {
                    $skipped++;
                    $errors[] = new VehicleBulkDeleteErrorDTO(null, CatalogMutationRejectReasonEnum::NotFound->value, 'ms_id:'.$msId);

                    continue;
                }

                if ($vehicle->provider === ProviderEnum::TD) {
                    $failed++;
                    $errors[] = new VehicleBulkDeleteErrorDTO($vehicle->id, CatalogMutationRejectReasonEnum::ProviderDeleteForbidden->value, 'ms_id:'.$msId);

                    continue;
                }

                $deleteIds[] = (int) $vehicle->id;
                event(new VehicleDeleted($request->userId, $request->operationId, $msId, (int) $vehicle->id));
            }

            $this->cascade->deleteVehiclesByIds($deleteIds);
            $result = $this->result($request, count($deleteIds), $skipped, $failed, $errors);
            $this->notifier->notify($result);

            return $result;
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->notifier->notify($this->failedResult($request));

            throw $e;
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        return array_values(array_unique($ids));
    }

    /**
     * Собирает DTO результата bulk-delete автомобилей.
     *
     * @param  list<VehicleBulkDeleteErrorDTO>  $errors
     */
    private function result(
        VehicleBulkDeleteRequestDTO $request,
        int $deleted,
        int $skipped,
        int $failed,
        array $errors,
    ): VehicleBulkDeleteResultDTO {
        return new VehicleBulkDeleteResultDTO(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            status: $errors === [] ? CatalogMutationStatusEnum::Completed : CatalogMutationStatusEnum::CompletedWithErrors,
            requested: count($request->msIds),
            deleted: $deleted,
            skipped: $skipped,
            failed: $failed,
            errors: $errors,
        );
    }

    /**
     * Собирает failed result для технической ошибки, сорвавшей всю операцию.
     */
    private function failedResult(VehicleBulkDeleteRequestDTO $request): VehicleBulkDeleteResultDTO
    {
        return $this->result($request, 0, 0, count($request->msIds), [new VehicleBulkDeleteErrorDTO(null, 'technical_failure')]);
    }
}

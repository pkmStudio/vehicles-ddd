<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;

/**
 * Оркестрирует CRM detail-сценарий Vehicles.
 */
final readonly class ShowVehicleForCrmUseCase implements ShowVehicleForCrmUseCaseInterface
{
    /**
     * Получает read repository порт Vehicles CRM.
     */
    public function __construct(
        private VehicleCrmReadRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает detail-снимок ТС или null.
     */
    public function execute(int $id): ?VehicleCrmDetailDTO
    {
        return $this->vehicles->find($id);
    }
}

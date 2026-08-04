<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;

/**
 * Оркестрирует CRM detail-сценарий Vehicles.
 */
final readonly class ShowVehicleForCrmUseCase implements ShowVehicleForCrmUseCaseInterface
{
    /**
     * Получает repository-порт Vehicles CRM.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает detail-снимок ТС или null.
     */
    public function execute(int $id): ?VehicleCrmDetailDTO
    {
        return $this->vehicles->findById($id);
    }
}

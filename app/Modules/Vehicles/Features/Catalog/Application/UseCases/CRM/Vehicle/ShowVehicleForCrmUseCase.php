<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;

/**
 * Оркестрирует CRM-сценарий просмотра ТС.
 */
final readonly class ShowVehicleForCrmUseCase implements ShowVehicleForCrmUseCaseInterface
{
    /**
     * Получает порт репозитория ТС для CRM.
     *
     * Шаги:
     * - Сохранить репозиторий для чтения detail-снимка ТС.
     *
     * Шаги:
     * 1) Принять read-порт CRM-каталога Vehicles.
     * 2) Сохранить его для detail-сценария по catalog id.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает detail-снимок ТС или null.
     *
     * Шаги:
     * - Запросить ТС по внутреннему catalog id.
     * - Вернуть DTO репозитория без изменения состава данных.
     *
     * Шаги:
     * 1) Передать catalog id автомобиля в CRM repository.
     * 2) Вернуть detail DTO или null, если запись не найдена.
     */
    public function execute(int $id): ?VehicleCrmListItemDTO
    {
        return $this->vehicles->findById($id);
    }
}

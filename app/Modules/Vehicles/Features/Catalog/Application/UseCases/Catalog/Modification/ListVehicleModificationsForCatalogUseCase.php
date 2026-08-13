<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список модификаций разрешённого ТС.
 */
final readonly class ListVehicleModificationsForCatalogUseCase implements ListVehicleModificationsForCatalogUseCaseInterface
{
    /**
     * Подключает репозитории ТС и модификаций.
     *
     * Шаги:
     * - Сохранить зависимости для проверки доступности ТС и чтения его модификаций.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
    ) {}

    /**
     * Возвращает модификации разрешённого ТС.
     *
     * Шаги:
     * - Найти ТС по catalog id.
     * - Вернуть null, если ТС отсутствует или закрыто для публичного каталога.
     * - Прочитать модификации ТС и преобразовать их в DTO публичного каталога.
     *
     * @return Collection<int, CatalogModificationDTO>|null
     */
    public function execute(int $vehicleId): ?Collection
    {
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null || ! $vehicle->isAllow) {
            return null;
        }

        return $this->modifications
            ->findByVehicleId($vehicleId)
            ->map(static fn (ModificationData $modification): CatalogModificationDTO => CatalogModificationDTO::fromData($modification))
            ->values();
    }
}

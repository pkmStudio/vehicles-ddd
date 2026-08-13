<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Оркестрирует CRM-сценарий чтения списка ТС.
 */
final readonly class ListVehiclesForCrmUseCase implements ListVehiclesForCrmUseCaseInterface
{
    /**
     * Получает порт репозитория ТС для CRM.
     *
     * Шаги:
     * - Сохранить репозиторий для постраничного чтения ТС.
     *
     * Шаги:
     * 1) Принять read-порт CRM-каталога Vehicles.
     * 2) Сохранить его для выполнения paginated read-сценария.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает постраничный список ТС для CRM.
     *
     * Шаги:
     * - Передать DTO запроса в репозиторий CRM-чтения.
     * - Вернуть готовую страницу без дополнительного преобразования.
     *
     * Шаги:
     * 1) Получить нормализованный query DTO от HTTP boundary.
     * 2) Передать paging/filter/sort параметры в repository.
     * 3) Вернуть page DTO без дополнительной трансформации.
     */
    public function execute(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO
    {
        return $this->vehicles->paginate($query);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCrmClient implements VehicleCrmClientInterface
{
    /**
     * Инициализирует CRM read client для Vehicles.
     *
     * Шаги:
     * 1. Получает use case для списка, detail, поиска и options.
     * 2. Сохраняет зависимости как read-only фасад CRM сценариев.
     */
    public function __construct(
        private ListVehiclesForCrmUseCaseInterface $listVehicles,
        private ShowVehicleForCrmUseCaseInterface $showVehicle,
        private SearchVehiclesForCrmUseCaseInterface $searchVehicles,
        private ListVehicleCrmOptionsUseCaseInterface $vehicleOptions,
    ) {}

    /**
     * Возвращает постраничный список Vehicles для CRM.
     *
     * Шаги:
     * 1. Принимает уже собранный read-query DTO.
     * 2. Делегирует выполнение use case списка.
     * 3. Возвращает page DTO без доступа к БД из client.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO
    {
        return $this->listVehicles->execute($query);
    }

    /**
     * Возвращает detail-снимок Vehicle для CRM.
     *
     * Шаги:
     * 1. Принимает внутренний id автомобиля.
     * 2. Делегирует lookup detail use case.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?VehicleCrmDetailDTO
    {
        return $this->showVehicle->execute($id);
    }

    /**
     * Возвращает compact search options Vehicles для CRM.
     *
     * Шаги:
     * 1. Принимает строку поиска и лимит результата.
     * 2. Делегирует поиск use case.
     * 3. Возвращает collection DTO/options для presenter boundary.
     */
    public function search(string $query, int $limit): Collection
    {
        return $this->searchVehicles->execute(
            query: $query,
            limit: $limit,
        );
    }

    /**
     * Возвращает options характеристик Vehicles для CRM-формы.
     *
     * Шаги:
     * 1. Делегирует запрос options use case.
     * 2. Возвращает collection DTO без дополнительного mapping.
     */
    public function features(): Collection
    {
        return $this->vehicleOptions->features();
    }

    /**
     * Возвращает options значений характеристики Vehicles для CRM-формы.
     *
     * Шаги:
     * 1. Принимает id характеристики.
     * 2. Делегирует запрос options use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     */
    public function featureValues(int $featureId): Collection
    {
        return $this->vehicleOptions->featureValues($featureId);
    }

    /**
     * Возвращает options detail templates для CRM-формы.
     *
     * Шаги:
     * 1. Делегирует запрос options use case.
     * 2. Возвращает collection DTO без дополнительного mapping.
     */
    public function detailTemplates(): Collection
    {
        return $this->vehicleOptions->detailTemplates();
    }

    /**
     * Возвращает options производителей для CRM-формы.
     *
     * Шаги:
     * 1. Принимает optional search query, selected id и limit.
     * 2. Делегирует запрос options use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->vehicleOptions->manufacturers(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }
}

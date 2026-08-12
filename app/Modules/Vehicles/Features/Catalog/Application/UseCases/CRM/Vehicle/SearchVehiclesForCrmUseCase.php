<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM-сценарий быстрого поиска ТС.
 */
final readonly class SearchVehiclesForCrmUseCase implements SearchVehiclesForCrmUseCaseInterface
{
    /**
     * Получает порт репозитория ТС для CRM.
     *
     * Шаги:
     * - Сохранить репозиторий для compact-поиска ТС.
     *
     * Шаги:
     * 1) Принять read-порт CRM-каталога Vehicles.
     * 2) Сохранить его для compact search-сценария.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает короткие варианты ТС для поля поиска.
     *
     * Шаги:
     * - Передать поисковую строку и лимит в репозиторий CRM-чтения.
     * - Вернуть найденные варианты в исходном порядке репозитория.
     *
     * Шаги:
     * 1) Передать поисковую строку и limit в repository.
     * 2) Вернуть compact DTO options для autocomplete/select UI.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function execute(string $query, int $limit = 20): Collection
    {
        return $this->vehicles->search($query, $limit);
    }
}

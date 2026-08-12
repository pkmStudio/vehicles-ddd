<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM-сценарии справочников формы ТС.
 */
final readonly class ListVehicleCrmOptionsUseCase implements ListVehicleCrmOptionsUseCaseInterface
{
    /**
     * Получает порт репозитория ТС для CRM.
     *
     * Шаги:
     * - Сохранить репозиторий для чтения справочников формы.
     *
     * Шаги:
     * 1) Принять read-порт CRM-каталога Vehicles.
     * 2) Использовать его как единственный источник dynamic options.
     */
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает варианты характеристик ТС.
     *
     * Шаги:
     * - Запросить список характеристик через репозиторий CRM-чтения.
     * - Вернуть коллекцию DTO без дополнительной фильтрации.
     *
     * Шаги:
     * 1) Запросить feature options через CRM repository.
     * 2) Вернуть коллекцию DTO без дополнительной фильтрации в use case.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection
    {
        return $this->vehicles->featureOptions();
    }

    /**
     * Возвращает варианты значений характеристики.
     *
     * Шаги:
     * - Передать id характеристики в репозиторий CRM-чтения.
     * - Вернуть значения, относящиеся к выбранной характеристике.
     *
     * Шаги:
     * 1) Передать id выбранной feature в CRM repository.
     * 2) Вернуть значения только для этой feature в формате option DTO.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection
    {
        return $this->vehicles->featureValueOptions($featureId);
    }

    /**
     * Возвращает варианты шаблонов деталей ТС.
     *
     * Шаги:
     * - Сформировать локальный список поддерживаемых detail-шаблонов.
     * - Вернуть коллекцию DTO для CRM-формы.
     *
     * Шаги:
     * 1) Собрать локальный список поддерживаемых templates для CRM формы Vehicle.
     * 2) Вернуть wiper template как option DTO с wire id и человекочитаемым label.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection
    {
        return collect([
            new VehicleCrmDetailTemplateOptionDTO(
                id: 'wiper',
                label: 'Щетки стеклоочистителя',
            ),
        ]);
    }

    /**
     * Возвращает варианты производителей для CRM-формы.
     *
     * Шаги:
     * - Передать поисковую строку, выбранный id и лимит в репозиторий.
     * - Вернуть найденных производителей в формате DTO options.
     *
     * Шаги:
     * 1) Передать поисковую строку, конкретный id и limit в CRM repository.
     * 2) Вернуть найденных производителей в формате option DTO.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->vehicles->manufacturerOptions($query, $id, $limit);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use Illuminate\Support\Collection;

/**
 * Use case port справочных options CRM API Vehicles.
 */
interface ListVehicleCrmOptionsUseCaseInterface
{
    /**
     * Возвращает feature options.
     *
     * Шаги:
     * 1) Прочитать feature справочник для CRM формы автомобиля.
     * 2) Вернуть collection option DTO.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection;

    /**
     * Возвращает feature value options.
     *
     * Шаги:
     * 1) Ограничить значения переданным feature id.
     * 2) Вернуть collection option DTO для выбранной feature.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection;

    /**
     * Возвращает detail template options.
     *
     * Шаги:
     * 1) Определить templates, поддержанные CRM vehicle form.
     * 2) Вернуть collection template option DTO.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection;

    /**
     * Возвращает manufacturer options.
     *
     * Шаги:
     * 1) Применить optional query/id/limit к справочнику производителей.
     * 2) Вернуть collection manufacturer option DTO.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturers(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}

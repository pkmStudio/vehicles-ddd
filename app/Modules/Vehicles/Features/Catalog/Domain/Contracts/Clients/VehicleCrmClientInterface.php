<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

interface VehicleCrmClientInterface
{
    /**
     * Возвращает постраничный CRM-список ТС.
     *
     * Шаги:
     * 1) Принять нормализованный query DTO от boundary.
     * 2) Прочитать page из owner read-сценария Vehicles CRM.
     * 3) Вернуть DTO страницы без Eloquent/paginator объектов.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    /**
     * Возвращает детальный CRM-снимок ТС или null.
     *
     * Шаги:
     * 1) Найти автомобиль по catalog id.
     * 2) Собрать detail DTO со связанными модификациями, двигателями и specifications.
     * 3) Вернуть null, если автомобиль отсутствует.
     */
    public function show(int $id): ?VehicleCrmListItemDTO;

    public function modifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    public function engines(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    public function partSpecifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    /**
     * Возвращает compact options для CRM поиска автомобилей.
     *
     * Шаги:
     * 1) Применить поисковую строку и limit к CRM read-модели.
     * 2) Вернуть collection search item DTO для autocomplete/select UI.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit): Collection;

    /**
     * Возвращает feature options для CRM фильтров/форм.
     *
     * Шаги:
     * 1) Прочитать справочник features, используемый спецификациями автомобилей.
     * 2) Вернуть collection option DTO.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function features(): Collection;

    /**
     * Возвращает feature value options для выбранной feature.
     *
     * Шаги:
     * 1) Ограничить значения переданным feature id.
     * 2) Вернуть collection option DTO.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValues(int $featureId): Collection;

    /**
     * Возвращает templates, доступные для vehicle part specifications в CRM.
     *
     * Шаги:
     * 1) Прочитать поддерживаемые detail templates для CRM формы.
     * 2) Вернуть collection template option DTO.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplates(): Collection;
}

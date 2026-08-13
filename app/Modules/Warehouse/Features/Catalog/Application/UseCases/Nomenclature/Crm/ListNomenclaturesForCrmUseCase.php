<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ListNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии списка и справочных options Warehouse-номенклатуры.
 */
final readonly class ListNomenclaturesForCrmUseCase implements ListNomenclaturesForCrmUseCaseInterface
{
    /**
     * Инициализирует зависимости сценария.
     *
     * Шаги:
     * 1) Принять CRM repository номенклатуры.
     * 2) Использовать repository для page, type options и brand options.
     */
    public function __construct(
        private NomenclatureCrmRepositoryInterface $nomenclatures,
    ) {}

    /**
     * Возвращает страницу данных для CRM-чтения.
     *
     * Шаги:
     * 1) Получить DTO параметров CRM-чтения.
     * 2) Передать фильтры, сортировку и пагинацию в репозиторий.
     * 3) Вернуть готовую страницу DTO без дополнительного преобразования.
     */
    public function execute(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
    {
        return $this->nomenclatures->paginate($query);
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     *
     * Шаги:
     * 1) Принять поисковую строку, выбранный id и лимит options.
     * 2) Запросить варианты типов в репозиторий.
     * 3) Вернуть коллекцию DTO для CRM-select.
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->nomenclatures->typeOptions($query, $id, $limit);
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     *
     * Шаги:
     * 1) Принять поисковую строку, выбранный id и лимит options.
     * 2) Запросить варианты брендов в репозиторий.
     * 3) Вернуть коллекцию DTO для CRM-select.
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->nomenclatures->brandOptions($query, $id, $limit);
    }
}

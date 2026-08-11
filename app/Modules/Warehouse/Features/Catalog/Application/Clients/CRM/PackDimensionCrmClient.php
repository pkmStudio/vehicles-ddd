<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\PackDimensionCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\Crm\ListPackDimensionsForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\Crm\ShowPackDimensionForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Read-only client CRM сценариев упаковочных размеров Warehouse.
 */
final readonly class PackDimensionCrmClient implements PackDimensionCrmClientInterface
{
    /**
     * Инициализирует CRM client упаковочных размеров.
     *
     * Шаги:
     * 1. Получает use case списка/options и detail упаковочных размеров.
     * 2. Сохраняет зависимости как thin facade CRM read API.
     */
    public function __construct(
        private ListPackDimensionsForCrmUseCaseInterface $list,
        private ShowPackDimensionForCrmUseCaseInterface $show,
    ) {}

    /**
     * Возвращает постраничный список упаковочных размеров для CRM.
     *
     * Шаги:
     * 1. Принимает готовый read-query DTO.
     * 2. Делегирует запрос list use case.
     * 3. Возвращает page DTO без доступа к БД из client.
     */
    public function paginate(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO
    {
        return $this->list->execute($query);
    }

    /**
     * Возвращает detail-снимок упаковочного размера для CRM.
     *
     * Шаги:
     * 1. Принимает внутренний id упаковочного размера.
     * 2. Делегирует lookup detail use case.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?PackDimensionCrmListItemDTO
    {
        return $this->show->execute($id);
    }

    /**
     * Возвращает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Принимает optional search query, selected id и limit.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->list->types($query, $id, $limit);
    }
}

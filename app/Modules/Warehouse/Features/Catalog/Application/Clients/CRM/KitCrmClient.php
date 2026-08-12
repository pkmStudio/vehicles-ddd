<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\Crm\ListKitsForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\Crm\ShowKitForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Read-only клиент CRM сценариев комплектов Warehouse.
 */
final readonly class KitCrmClient implements KitCrmClientInterface
{
    /**
     * Инициализирует CRM клиент комплектов.
     *
     * Шаги:
     * 1. Получает use case списка/options и detail комплектов.
     * 2. Сохраняет зависимости как thin facade CRM read API.
     */
    public function __construct(
        private ListKitsForCrmUseCaseInterface $list,
        private ShowKitForCrmUseCaseInterface $show,
    ) {}

    /**
     * Возвращает постраничный список комплектов для CRM.
     *
     * Шаги:
     * 1. Принимает готовый read-query DTO.
     * 2. Делегирует запрос list use case.
     * 3. Возвращает DTO страницы без доступа к БД из клиент.
     */
    public function paginate(KitCrmReadQueryDTO $query): KitCrmPageDTO
    {
        return $this->list->execute($query);
    }

    /**
     * Возвращает detail-снимок комплекта для CRM.
     *
     * Шаги:
     * 1. Принимает внутренний id комплекта.
     * 2. Делегирует поиск detail use case.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?KitCrmListItemDTO
    {
        return $this->show->execute($id);
    }

    /**
     * Возвращает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatures(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->list->nomenclatures($query, $id, $limit);
    }

    /**
     * Возвращает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->list->packDimensions($query, $id, $limit);
    }

    /**
     * Возвращает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->list->types($query, $id, $limit);
    }
}

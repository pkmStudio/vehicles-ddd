<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии списка и options комплектов.
 */
final readonly class ListKitsForCrmUseCase
{
    /**
     * Инициализирует порт репозитория комплектов для CRM read-сценариев.
     *
     * Шаги:
     * 1. Получает порт репозитория owner-слоя Catalog.
     * 2. Сохраняет port для всех read-запросов use case.
     */
    public function __construct(
        private KitCrmRepositoryInterface $kits,
    ) {}

    /**
     * Возвращает постраничный список комплектов для CRM.
     *
     * Шаги:
     * 1. Принимает read-query DTO.
     * 2. Делегирует построение страницы порт репозитория.
     * 3. Возвращает DTO страницы для границы клиента и контроллера.
     */
    public function execute(KitCrmReadQueryDTO $query): KitCrmPageDTO
    {
        return $this->kits->paginate($query);
    }

    /**
     * Возвращает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует options-поиск порт репозитория.
     * 3. Возвращает collection DTO без framework-specific ответ shape.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatures(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->kits->nomenclatureOptions($query, $id, $limit);
    }

    /**
     * Возвращает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует options-поиск порт репозитория.
     * 3. Возвращает collection DTO без framework-specific ответ shape.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->kits->packDimensionOptions($query, $id, $limit);
    }

    /**
     * Возвращает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует options-поиск порт репозитория.
     * 3. Возвращает collection DTO без framework-specific ответ shape.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->kits->typeOptions($query, $id, $limit);
    }
}

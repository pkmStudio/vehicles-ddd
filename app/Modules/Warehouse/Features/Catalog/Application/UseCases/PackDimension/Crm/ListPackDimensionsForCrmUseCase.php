<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\Crm\ListPackDimensionsForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии списка и options упаковочных размеров.
 */
final readonly class ListPackDimensionsForCrmUseCase implements ListPackDimensionsForCrmUseCaseInterface
{
    /**
     * Инициализирует порт репозитория упаковочных размеров для CRM read-сценариев.
     *
     * Шаги:
     * 1. Получает порт репозитория owner-слоя Catalog.
     * 2. Сохраняет port для всех read-запросов use case.
     */
    public function __construct(
        private PackDimensionCrmRepositoryInterface $packDimensions,
    ) {}

    /**
     * Возвращает постраничный список упаковочных размеров для CRM.
     *
     * Шаги:
     * 1. Принимает read-query DTO.
     * 2. Делегирует построение страницы порт репозитория.
     * 3. Возвращает DTO страницы для границы клиента и контроллера.
     */
    public function execute(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO
    {
        return $this->packDimensions->paginate($query);
    }

    /**
     * Возвращает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует options-поиск порт репозитория.
     * 3. Возвращает collection DTO без framework-specific ответ shape.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->packDimensions->typeOptions($query, $id, $limit);
    }
}

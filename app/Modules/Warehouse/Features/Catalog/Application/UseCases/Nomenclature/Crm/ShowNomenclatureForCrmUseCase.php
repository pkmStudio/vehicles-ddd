<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ShowNomenclatureForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;

/**
 * Оркестрирует CRM detail-сценарий Warehouse-номенклатуры.
 */
final readonly class ShowNomenclatureForCrmUseCase implements ShowNomenclatureForCrmUseCaseInterface
{
    /**
     * Инициализирует зависимости сценария.
     *
     * Шаги:
     * 1) Принять CRM repository номенклатуры.
     * 2) Использовать repository для detail lookup по id.
     */
    public function __construct(
        private NomenclatureCrmRepositoryInterface $nomenclatures,
    ) {}

    /**
     * Возвращает детальную CRM-проекцию по идентификатору.
     *
     * Шаги:
     * 1) Принять идентификатор записи каталога.
     * 2) Запросить детальную CRM-проекцию в репозиторий.
     * 3) Вернуть DTO или null, если запись не найдена.
     */
    public function execute(int $id): ?NomenclatureCrmListItemDTO
    {
        return $this->nomenclatures->findById($id);
    }
}

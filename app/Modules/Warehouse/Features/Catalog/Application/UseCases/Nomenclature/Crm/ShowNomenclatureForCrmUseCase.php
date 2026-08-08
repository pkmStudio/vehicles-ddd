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
    public function __construct(
        private NomenclatureCrmRepositoryInterface $nomenclatures,
    ) {}

    public function execute(int $id): ?NomenclatureCrmListItemDTO
    {
        return $this->nomenclatures->findById($id);
    }
}
